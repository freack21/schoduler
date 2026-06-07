<?php

namespace App\Jobs;

use App\Models\GuruMapel;
use App\Models\Jadwal;
use App\Models\JamPelajaran;
use App\Models\Pengaturan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\ScheduleGeneration;

class GenerateScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900;

    // Tuned GA parameters
    private int $populationSize = 80;
    private int $maxGenerations = 500;
    private float $crossoverRate = 0.85; 
    private float $mutationRate = 0.20;  
    private int $eliteCount = 8;         
    
    private int $scheduleGenerationId;

    public function __construct(int $scheduleGenerationId)
    {
        $this->scheduleGenerationId = $scheduleGenerationId;
    }

    public function handle(): void
    {
        $genState = ScheduleGeneration::find($this->scheduleGenerationId);
        if (!$genState) {
            return;
        }

        $genState->update([
            'status' => 'running',
            'generation' => 0,
            'fitness' => 0,
            'violations' => 0,
            'max_generations' => $this->maxGenerations,
        ]);

        $guruMapels = GuruMapel::with(['mapel', 'guru', 'kelas'])->get();
        $jamPelajaranList = JamPelajaran::orderBy('jam_ke')->get();
        $allDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $dbDays = JamPelajaran::select('hari')->distinct()->pluck('hari')->toArray();
        $hariAktif = array_values(array_intersect($allDays, $dbDays));

        if ($guruMapels->isEmpty() || $jamPelajaranList->isEmpty()) {
            $genState->update([
                'status' => 'error',
                'message' => 'Data guru_mapel atau jam pelajaran kosong.',
                'completed_at' => now(),
            ]);
            return;
        }

        // Build gene and block list
        $genes = [];
        $blocks = [];
        $mapelJamPerHari = [];
        $mapelJamPerMinggu = [];
        $geneIdx = 0;
        foreach ($guruMapels as $gm) {
            $jamPerHari = max(1, (int) $gm->mapel->jam_per_hari); // Failsafe
            $jam = $gm->mapel->jam_per_minggu;
            $mapelJamPerHari[$gm->mapel_id] = $jamPerHari;
            $mapelJamPerMinggu[$gm->mapel_id] = $jam;
            
            $blockSizes = [];
            while ($jam > 0) {
                $size = min($jam, $jamPerHari);
                if ($size < 1) $size = 1; // Failsafe against infinite loops
                $blockSizes[] = $size;
                $jam -= $size;
            }
            
            foreach ($blockSizes as $size) {
                $currentBlock = [];
                for ($j = 0; $j < $size; $j++) {
                    $genes[] = [
                        'guru_mapel_id' => $gm->id,
                        'guru_id' => $gm->guru_id,
                        'kelas_id' => $gm->kelas_id,
                        'mapel_id' => $gm->mapel_id,
                    ];
                    $currentBlock[] = $geneIdx++;
                }
                $blocks[] = $currentBlock;
            }
        }

        $jamPelajaranAll = JamPelajaran::orderBy('jam_ke')->get();

        $blocks = array_values($blocks);
        
        // SORT BLOCKS DESCENDING BY SIZE (Largest Enumerable First Heuristic)
        // This prevents small blocks from creating "holes" that block large blocks later.
        usort($blocks, function($a, $b) {
            return count($b) <=> count($a);
        });

        $totalGenes = count($genes);
        $totalBlocks = count($blocks);

        // Build slot map
        $slotMap = [];
        $totalHari = count($hariAktif);

        foreach ($hariAktif as $hariIdx => $hari) {
            $jamsForDay = $jamPelajaranAll->where('hari', $hari);
            
            $pos = 1;
            foreach ($jamsForDay as $jp) {
                if (!$jp->is_istirahat) {
                    $slotMap[] = [
                        'hari' => $hari,
                        'jam_pelajaran_id' => $jp->id,
                        'hari_idx' => $hariIdx,
                        'jam_pos' => $pos++,
                    ];
                }
            }
        }
        $totalSlots = count($slotMap);

        if ($totalSlots === 0 || $totalBlocks === 0) {
            $genState->update([
                'status' => 'error',
                'message' => 'Tidak ada slot waktu tersedia atau tidak ada mapel dengan jam_per_minggu > 0.',
                'completed_at' => now(),
            ]);
            return;
        }

        $bestOverallScore = null;
        $bestOverallHard = null;
        $bestOverallDist = null;
        $bestOverallFitness = null;

        // ── STRUCTURAL: compute allowed slots per kelas and block valid starts ──
        $kelasLessonCount = [];
        foreach ($genes as $gene) {
            $kelasLessonCount[$gene['kelas_id']] = ($kelasLessonCount[$gene['kelas_id']] ?? 0) + 1;
        }

        $allSlots = range(0, $totalSlots - 1);
        $kelasAllowedSlots = [];
        foreach ($kelasLessonCount as $kelasId => $lessonCount) {
            $kelasAllowedSlots[$kelasId] = $allSlots;
        }

        $validBlockStarts = [];
        for ($size = 1; $size <= 4; $size++) {
            $validBlockStarts[$size] = [];
            for ($s = 0; $s <= $totalSlots - $size; $s++) {
                // Check if slot $s to $s+$size-1 are on the same day
                $startHari = $slotMap[$s]['hari_idx'];
                $endHari = $slotMap[$s + $size - 1]['hari_idx'];
                if ($startHari === $endHari) {
                    // Also check jam_pos is consecutive (no break gap)
                    $isConsecutive = true;
                    for ($j = 1; $j < $size; $j++) {
                        if ($slotMap[$s + $j]['jam_pos'] !== $slotMap[$s + $j - 1]['jam_pos'] + 1) {
                            $isConsecutive = false;
                            break;
                        }
                    }
                    if ($isConsecutive) {
                        $validBlockStarts[$size][] = $s;
                    }
                }
            }
            // Fallback if no contiguous slots are available for this size
            if (empty($validBlockStarts[$size])) {
                $validBlockStarts[$size] = $validBlockStarts[1];
            }
        }

        // Compute per-guru total load for priority scheduling
        $guruTotalLoad = [];
        foreach ($genes as $gene) {
            $guruTotalLoad[$gene['guru_id']] = ($guruTotalLoad[$gene['guru_id']] ?? 0) + 1;
        }

        $evalContext = [
            'genes' => $genes,
            'blocks' => $blocks,
            'slotMap' => $slotMap,
            'mapelJamPerHari' => $mapelJamPerHari,
            'mapelJamPerMinggu' => $mapelJamPerMinggu,
            'kelasAllowedSlots' => $kelasAllowedSlots,
            'validBlockStarts' => $validBlockStarts,
            'guruTotalLoad' => $guruTotalLoad,
        ];

        // ── INITIAL POPULATION (Block Based) ──
        $population = [];
        $population[] = $this->createSmartChromosome($evalContext);

        $smartCount = max(10, (int) round($this->populationSize * 0.60));
        for ($i = 1; $i < $this->populationSize; $i++) {
            if ($i <= $smartCount) {
                $population[] = $this->createSmartChromosome($evalContext);
            } else {
                $chromosome = [];
                for ($b = 0; $b < $totalBlocks; $b++) {
                    $size = count($blocks[$b]);
                    $validStarts = $validBlockStarts[$size];
                    $chromosome[] = $validStarts[array_rand($validStarts)];
                }
                $population[] = $chromosome;
            }
        }

        $bestChromosome = null;
        $bestScore = PHP_INT_MAX;

        // ── EVOLUTION LOOP ──
        $stagnantGenerations = 0;
        $lastBestScore = PHP_INT_MAX;
        for ($gen = 0; $gen < $this->maxGenerations; $gen++) {
            $scores = [];
            foreach ($population as $chromosome) {
                $scores[] = $this->evaluate($chromosome, $evalContext);
            }

            $bestIdx = 0;
            $currentBestScore = $scores[0]['total'];
            foreach ($scores as $idx => $scoreData) {
                if ($scoreData['total'] < $currentBestScore) {
                    $currentBestScore = $scoreData['total'];
                    $bestIdx = $idx;
                }
            }

            if ($currentBestScore < $bestScore) {
                $bestScore = $currentBestScore;
                $bestChromosome = $population[$bestIdx];
                $bestOverallHard = $scores[$bestIdx]['guru_conflicts'] + $scores[$bestIdx]['kelas_conflicts'];
                $bestOverallDist = $scores[$bestIdx]['dist_violations'];
                $bestOverallFitness = 1.0 / (1.0 + $bestScore);
            }

            $currentFitness = round(1.0 / (1.0 + $currentBestScore), 6);
            
            // Limit DB writes to every 10 generations or on finish
            if ($gen % 10 === 0 || $bestScore === 0) {
                $genState->update([
                    'generation' => $gen + 1,
                    'fitness' => $currentFitness,
                    'violations' => $bestOverallHard ?? 0,
                    'dist_violations' => $bestOverallDist ?? 0,
                ]);
            }

            if ($bestScore === 0) {
                $genState->update([
                    'generation' => $gen + 1,
                    'fitness' => 1.0,
                ]);
                break;
            }

            // Local search — run every 5 generations for faster convergence
            if ($gen > 0 && $gen % 5 === 0 && $bestChromosome) {
                $improved = $this->localSearch($bestChromosome, $evalContext);
                $improvedScore = $this->evaluate($improved, $evalContext)['total'];
                if ($improvedScore < $bestScore) {
                    $bestScore = $improvedScore;
                    $bestChromosome = $improved;
                    $population[0] = $bestChromosome;
                }
            }

            // Preservation & Elitism
            $indexed = [];
            foreach ($population as $idx => $chrom) {
                $indexed[] = ['c' => $chrom, 's' => $scores[$idx]['total']];
            }
            usort($indexed, fn($a, $b) => $a['s'] <=> $b['s']);
            $newPop = [];
            for ($i = 0; $i < $this->eliteCount; $i++) {
                $newPop[] = $indexed[$i]['c'];
            }

            $fitnessValues = array_map(fn($s) => 1.0 / (1.0 + $s['total']), $scores);

            while (count($newPop) < $this->populationSize) {
                $p1 = $this->tournamentSelect($population, $fitnessValues);
                $p2 = $this->tournamentSelect($population, $fitnessValues);

                if ($this->randFloat() < $this->crossoverRate) {
                    [$c1, $c2] = $this->classPreservingCrossover($p1, $p2, $evalContext);
                } else {
                    $c1 = $p1;
                    $c2 = $p2;
                }

                $c1 = $this->smartMutate($c1, $evalContext, $totalSlots);
                $c2 = $this->smartMutate($c2, $evalContext, $totalSlots);
                
                $newPop[] = $c1;
                if (count($newPop) < $this->populationSize) {
                    $newPop[] = $c2;
                }
            }
            $population = $newPop;

            if ($bestScore < $lastBestScore) {
                $stagnantGenerations = 0;
                $lastBestScore = $bestScore;
            } else {
                $stagnantGenerations++;
            }
            
            if ($stagnantGenerations >= 30) {
                $keepCount = (int) ceil($this->populationSize * 0.15);
                $newPop = [];
                for ($i = 0; $i < $keepCount; $i++) {
                    $newPop[] = $indexed[$i]['c'];
                }
                // Inject 50% fresh smart chromosomes for diversity
                $freshCount = (int)(($this->populationSize - $keepCount) * 0.5);
                for ($i = 0; $i < $freshCount; $i++) {
                    $newPop[] = $this->createSmartChromosome($evalContext);
                }
                // Fill rest with crossover
                while (count($newPop) < $this->populationSize) {
                    $p1 = $this->tournamentSelect($population, $fitnessValues);
                    $p2 = $this->tournamentSelect($population, $fitnessValues);
                    [$c1, $c2] = $this->classPreservingCrossover($p1, $p2, $evalContext);
                    $c1 = $this->smartMutate($c1, $evalContext, $totalSlots);
                    $c2 = $this->smartMutate($c2, $evalContext, $totalSlots);
                    $newPop[] = $c1;
                    if (count($newPop) < $this->populationSize) {
                        $newPop[] = $c2;
                    }
                }
                $population = $newPop;
                $stagnantGenerations = 0;
            }
        }

        // ── POST-GA REPAIR PHASE ──
        // Systematically resolve remaining hard conflicts
        if ($bestChromosome) {
            $repairRounds = 0;
            $maxRepairRounds = 50;
            
            while ($repairRounds < $maxRepairRounds) {
                $eval = $this->evaluate($bestChromosome, $evalContext);
                $hardConflicts = $eval['guru_conflicts'] + $eval['kelas_conflicts'];
                
                if ($hardConflicts === 0) break;
                
                $conflictingBlocks = $eval['conflicting_blocks'];
                if (empty($conflictingBlocks)) break;
                
                $improved = false;
                
                // Try to fix each conflicting block
                foreach ($conflictingBlocks as $b) {
                    $blockSize = count($blocks[$b]);
                    $validStarts = $validBlockStarts[$blockSize];
                    $currentScore = $eval['total'];
                    
                    $bestNewSlot = $bestChromosome[$b];
                    $bestNewScore = $currentScore;
                    
                    // Try every valid position
                    foreach ($validStarts as $s) {
                        if ($s === $bestChromosome[$b]) continue;
                        $trial = $bestChromosome;
                        $trial[$b] = $s;
                        $trialEval = $this->evaluate($trial, $evalContext);
                        $trialHard = $trialEval['guru_conflicts'] + $trialEval['kelas_conflicts'];
                        
                        if ($trialEval['total'] < $bestNewScore) {
                            $bestNewScore = $trialEval['total'];
                            $bestNewSlot = $s;
                            $improved = true;
                        }
                    }
                    
                    if ($bestNewSlot !== $bestChromosome[$b]) {
                        $bestChromosome[$b] = $bestNewSlot;
                        $bestScore = $bestNewScore;
                    }
                }
                
                if (!$improved) break;
                $repairRounds++;
            }
            
            // Update best score after repair
            $eval = $this->evaluate($bestChromosome, $evalContext);
            $bestScore = $eval['total'];
            $bestOverallHard = $eval['guru_conflicts'] + $eval['kelas_conflicts'];
            $bestOverallDist = $eval['dist_violations'];
            $bestOverallFitness = 1.0 / (1.0 + $bestScore);
            
            Log::info("REPAIR: {$repairRounds} rounds, Hard={$bestOverallHard}, Score={$bestScore}");
        }

        // ── SAVE RESULT ──
        Jadwal::truncate();

        if ($bestChromosome) {
            $entries = [];
            foreach ($bestChromosome as $b => $startSlot) {
                $blockGenes = $blocks[$b];
                foreach ($blockGenes as $offset => $geneIdx) {
                    $slot = $slotMap[$startSlot + $offset];
                    $entries[] = [
                        'guru_mapel_id' => $genes[$geneIdx]['guru_mapel_id'],
                        'hari' => $slot['hari'],
                        'jam_pelajaran_id' => $slot['jam_pelajaran_id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            Jadwal::insert($entries);
        }

        $final = $bestChromosome
            ? $this->evaluate($bestChromosome, $evalContext)
            : ['guru_conflicts' => -1, 'kelas_conflicts' => -1, 'dist_violations' => -1, 'consecutive_violations' => -1, 'gap_violations' => -1, 'total' => -1, 'day_priority_penalty' => -1];

        $hard = $final['guru_conflicts'] + $final['kelas_conflicts'];

        $finalFitness = round(1.0 / (1.0 + $final['total']), 6);
        $finalMessage = '';

        if ($hard === 0 && $final['dist_violations'] === 0 && $final['consecutive_violations'] === 0) {
            $finalMessage = 'Jadwal berhasil digenerate tanpa bentrok, blok rapi! 🎯';
        } elseif ($hard === 0) {
            $finalMessage = "Jadwal tanpa bentrok! Soft: {$final['dist_violations']} distribusi, {$final['consecutive_violations']} urutan.";
        } else {
            $finalMessage = "Jadwal digenerate dengan {$hard} bentrok. Perlu di-generate ulang.";
        }

        $genState->update([
            'status' => 'done',
            'fitness' => $finalFitness,
            'violations' => $hard,
            'dist_violations' => $final['dist_violations'],
            'message' => $finalMessage,
            'completed_at' => now(),
        ]);

        Log::info("GA DONE: Score={$final['total']}, Hard={$hard}, Dist={$final['dist_violations']}, Cons={$final['consecutive_violations']}");
    }

    private function createSmartChromosome(array $ctx): array
    {
        $blocks = $ctx['blocks'];
        $genes = $ctx['genes'];
        $validBlockStarts = $ctx['validBlockStarts'];
        $slotMap = $ctx['slotMap'];
        $guruTotalLoad = $ctx['guruTotalLoad'] ?? [];
        
        $totalBlocks = count($blocks);
        $chromosome = array_fill(0, $totalBlocks, 0);
        $usedGuruSlots = [];
        $usedKelasSlots = [];
        $guruDayLoad = [];

        $indices = range(0, $totalBlocks - 1);
        
        // Sort by: 1) guru total load descending (hardest guru first), 2) block size descending
        usort($indices, function($a, $b) use ($blocks, $genes, $guruTotalLoad) {
            $guruA = $genes[$blocks[$a][0]]['guru_id'];
            $guruB = $genes[$blocks[$b][0]]['guru_id'];
            $loadA = $guruTotalLoad[$guruA] ?? 0;
            $loadB = $guruTotalLoad[$guruB] ?? 0;
            $loadComp = $loadB <=> $loadA; // Most loaded guru first
            if ($loadComp !== 0) return $loadComp;
            return count($blocks[$b]) <=> count($blocks[$a]); // Largest block first
        });
        
        // Shuffle within same (guru_load, block_size) groups for diversity
        $groups = [];
        foreach ($indices as $idx) {
            $guruId = $genes[$blocks[$idx][0]]['guru_id'];
            $key = ($guruTotalLoad[$guruId] ?? 0) . '-' . count($blocks[$idx]);
            $groups[$key][] = $idx;
        }
        $indices = [];
        foreach ($groups as $group) {
            shuffle($group);
            foreach ($group as $idx) {
                $indices[] = $idx;
            }
        }

        foreach ($indices as $b) {
            $blockSize = count($blocks[$b]);
            $firstGene = $genes[$blocks[$b][0]];
            $guruId = $firstGene['guru_id'];
            $kelasId = $firstGene['kelas_id'];
            
            $validStarts = $validBlockStarts[$blockSize];
            
            $zeroCandidates = [];
            $bestSlot = -1;
            $minConflicts = PHP_INT_MAX;
            $bestCandidates = [];
            
            foreach ($validStarts as $s) {
                $conflicts = 0;
                for ($offset = 0; $offset < $blockSize; $offset++) {
                    if (isset($usedGuruSlots[$guruId][$s + $offset])) $conflicts++;
                    if (isset($usedKelasSlots[$kelasId][$s + $offset])) $conflicts++;
                }
                
                if ($conflicts === 0) {
                    $zeroCandidates[] = $s;
                } elseif ($conflicts < $minConflicts) {
                    $minConflicts = $conflicts;
                    $bestCandidates = [$s];
                } elseif ($conflicts === $minConflicts) {
                    $bestCandidates[] = $s;
                }
            }

            if (!empty($zeroCandidates)) {
                // Among 0-conflict slots, prefer days where this guru has less load
                $scored = [];
                foreach ($zeroCandidates as $s) {
                    $dayIdx = $slotMap[$s]['hari_idx'];
                    $dayLoad = $guruDayLoad[$guruId][$dayIdx] ?? 0;
                    $scored[] = ['s' => $s, 'load' => $dayLoad];
                }
                // Sort by day load ascending (spread across days)
                usort($scored, fn($a, $b) => $a['load'] <=> $b['load']);
                // Pick from the least-loaded day options (with some randomness)
                $minLoad = $scored[0]['load'];
                $topCandidates = array_filter($scored, fn($x) => $x['load'] <= $minLoad + 1);
                $topCandidates = array_values($topCandidates);
                $bestSlot = $topCandidates[array_rand($topCandidates)]['s'];
            } elseif (!empty($bestCandidates)) {
                $bestSlot = $bestCandidates[array_rand($bestCandidates)];
            } else {
                $bestSlot = 0;
            }

            $chromosome[$b] = $bestSlot;
            for ($offset = 0; $offset < $blockSize; $offset++) {
                $usedGuruSlots[$guruId][$bestSlot + $offset] = true;
                $usedKelasSlots[$kelasId][$bestSlot + $offset] = true;
            }
            // Track day load
            $dayIdx = $slotMap[$bestSlot]['hari_idx'];
            $guruDayLoad[$guruId][$dayIdx] = ($guruDayLoad[$guruId][$dayIdx] ?? 0) + $blockSize;
        }
        return $chromosome;
    }

    public function evaluate(array $chromosome, array $ctx): array
    {
        $slotMap = $ctx['slotMap'];
        $blocks = $ctx['blocks'];
        $genes = $ctx['genes'];
        $mapelJamPerHari = $ctx['mapelJamPerHari'];
        $mapelJamPerMinggu = $ctx['mapelJamPerMinggu'];

        $guruSlots = [];
        $kelasSlots = [];
        $kelasMapelHari = [];

        $guruConflicts = 0;
        $conflictingBlocks = [];
        $kelasConflicts = 0;
        $distViolations = 0;
        $consecutiveViolations = 0;
        $dayPriorityPenalty = 0;

        foreach ($chromosome as $b => $startSlot) {
            $blockGenes = $blocks[$b];
            
            foreach ($blockGenes as $offset => $geneIdx) {
                $gene = $genes[$geneIdx];
                $slotIdx = $startSlot + $offset;
                $slot = $slotMap[$slotIdx];
                $hariIdx = $slot['hari_idx'];
                $jamPos = $slot['jam_pos'];

                $guruId = $gene['guru_id'];
                $kelasId = $gene['kelas_id'];
                $mapelId = $gene['mapel_id'];

                if (isset($guruSlots[$guruId][$slotIdx])) {
                    $guruConflicts++;
                    $conflictingBlocks[$b] = true;
                }
                $guruSlots[$guruId][$slotIdx] = true;

                if (isset($kelasSlots[$kelasId][$slotIdx])) {
                    $kelasConflicts++;
                    $conflictingBlocks[$b] = true;
                }
                $kelasSlots[$kelasId][$slotIdx] = true;

                $key = "{$kelasId}-{$mapelId}";
                $kelasMapelHari[$key][$hariIdx][] = $jamPos;
                
                // Day priority: heavy penalty for later days and later slots
                // Ensures Senin fills before Selasa, and mornings fill before afternoons
                $dayPriorityPenalty += ($hariIdx * 2) + ($jamPos * 0.1);
            }
        }

        // Penalize daily gaps (jam bolong) for each class
        $gapPenalty = 0;
        foreach ($kelasSlots as $kelasId => $occupiedSlots) {
            $slotsByDay = [];
            foreach (array_keys($occupiedSlots) as $slotIdx) {
                $hariIdx = $slotMap[$slotIdx]['hari_idx'];
                $jamPos = $slotMap[$slotIdx]['jam_pos'];
                $slotsByDay[$hariIdx][] = $jamPos;
            }

            foreach ($slotsByDay as $hariIdx => $jamPositions) {
                if (count($jamPositions) > 1) {
                    sort($jamPositions);
                    $min = $jamPositions[0];
                    $max = $jamPositions[count($jamPositions) - 1];
                    $expectedCount = $max - $min + 1;
                    $gaps = $expectedCount - count($jamPositions);
                    if ($gaps > 0) {
                        $gapPenalty += $gaps * 50; 
                    }
                }
            }
        }

        // Soft constraints
        foreach ($kelasMapelHari as $key => $hariData) {
            $mapelId = (int) explode('-', $key)[1];
            $jamPerHari = $mapelJamPerHari[$mapelId] ?? 2;
            $jamMinggu = $mapelJamPerMinggu[$mapelId] ?? 2;
            
            $idealDays = (int) ceil($jamMinggu / $jamPerHari);
            $actualDays = count($hariData);
            
            if ($actualDays > $idealDays) {
                $distViolations += ($actualDays - $idealDays) * 50; // heavy penalty for spread
            } elseif ($actualDays < $idealDays) {
                $distViolations += ($idealDays - $actualDays) * 50; // heavily penalize stacking on same day
            }

            foreach ($hariData as $hariIdx => $positions) {
                $count = count($positions);
                if ($count > $jamPerHari) {
                    $distViolations += ($count - $jamPerHari) * 100; // practically forbidden to exceed jam per hari
                }
                // Consecutive: already guaranteed within blocks, but check across blocks on same day
                if ($count > 1) {
                    sort($positions);
                    for ($i = 1; $i < $count; $i++) {
                        if ($positions[$i] !== $positions[$i - 1] + 1) {
                            $consecutiveViolations += 50; // heavy penalty for non-consecutive
                        }
                    }
                }
            }
        }

        $hardScore = ($guruConflicts * 1000) + ($kelasConflicts * 1000);
        $softScore = ($distViolations * 10) + ($consecutiveViolations * 5) + $gapPenalty + $dayPriorityPenalty;
        $total = $hardScore + $softScore;

        return [
            'guru_conflicts' => $guruConflicts,
            'kelas_conflicts' => $kelasConflicts,
            'dist_violations' => $distViolations,
            'consecutive_violations' => $consecutiveViolations,
            'gap_violations' => $gapPenalty,
            'total' => $total,
            'day_priority_penalty' => $dayPriorityPenalty,
            'conflicting_blocks' => array_keys($conflictingBlocks),
        ];
    }

    private function smartMutate(array $chromosome, array $ctx, int $totalSlots): array
    {
        if ($this->randFloat() > $this->mutationRate) {
            return $chromosome;
        }

        $blocks = $ctx['blocks'];
        $genes = $ctx['genes'];
        $validBlockStarts = $ctx['validBlockStarts'];
        $slotMap = $ctx['slotMap'];
        $totalBlocks = count($blocks);
        
        // Build a fast occupancy map from current chromosome
        $guruSlots = [];
        $kelasSlots = [];
        foreach ($chromosome as $b => $startSlot) {
            foreach ($blocks[$b] as $offset => $geneIdx) {
                $gene = $genes[$geneIdx];
                $sIdx = $startSlot + $offset;
                $guruSlots[$gene['guru_id']][$sIdx] = $b;
                $kelasSlots[$gene['kelas_id']][$sIdx] = $b;
            }
        }
        
        // Find which blocks have conflicts
        $conflictingBlocks = [];
        foreach ($chromosome as $b => $startSlot) {
            foreach ($blocks[$b] as $offset => $geneIdx) {
                $gene = $genes[$geneIdx];
                $sIdx = $startSlot + $offset;
                // Check if any other block occupies same guru+slot or kelas+slot
                if (isset($guruSlots[$gene['guru_id']][$sIdx]) && $guruSlots[$gene['guru_id']][$sIdx] !== $b) {
                    $conflictingBlocks[$b] = true;
                    break;
                }
                if (isset($kelasSlots[$gene['kelas_id']][$sIdx]) && $kelasSlots[$gene['kelas_id']][$sIdx] !== $b) {
                    $conflictingBlocks[$b] = true;
                    break;
                }
            }
        }
        $conflictingBlockIds = array_keys($conflictingBlocks);
        
        $numMutations = rand(1, 3);

        for ($m = 0; $m < $numMutations; $m++) {
            // Target conflicting blocks 80% of the time
            if (!empty($conflictingBlockIds) && $this->randFloat() > 0.2) {
                $b1 = $conflictingBlockIds[array_rand($conflictingBlockIds)];
            } else {
                $b1 = rand(0, $totalBlocks - 1);
            }
            
            $size1 = count($blocks[$b1]);
            
            if ($this->randFloat() > 0.4) {
                // Move to a random valid position — pick one with least conflicts using fast check
                $validStarts = $validBlockStarts[$size1];
                if (!empty($validStarts)) {
                    $firstGene = $genes[$blocks[$b1][0]];
                    $guruId = $firstGene['guru_id'];
                    $kelasId = $firstGene['kelas_id'];
                    
                    $bestSlot = $chromosome[$b1];
                    $bestConflicts = PHP_INT_MAX;
                    
                    // Sample a few candidates
                    $sampleSize = min(8, count($validStarts));
                    $sampleKeys = array_rand($validStarts, $sampleSize);
                    if (!is_array($sampleKeys)) $sampleKeys = [$sampleKeys];
                    
                    foreach ($sampleKeys as $key) {
                        $s = $validStarts[$key];
                        $conflicts = 0;
                        for ($offset = 0; $offset < $size1; $offset++) {
                            $sIdx = $s + $offset;
                            if (isset($guruSlots[$guruId][$sIdx]) && $guruSlots[$guruId][$sIdx] !== $b1) $conflicts++;
                            if (isset($kelasSlots[$kelasId][$sIdx]) && $kelasSlots[$kelasId][$sIdx] !== $b1) $conflicts++;
                        }
                        if ($conflicts < $bestConflicts) {
                            $bestConflicts = $conflicts;
                            $bestSlot = $s;
                            if ($conflicts === 0) break;
                        }
                    }
                    
                    // Update occupancy maps
                    foreach ($blocks[$b1] as $offset => $geneIdx) {
                        $gene = $genes[$geneIdx];
                        $oldSlot = $chromosome[$b1] + $offset;
                        if (isset($guruSlots[$gene['guru_id']][$oldSlot]) && $guruSlots[$gene['guru_id']][$oldSlot] === $b1) {
                            unset($guruSlots[$gene['guru_id']][$oldSlot]);
                        }
                        if (isset($kelasSlots[$gene['kelas_id']][$oldSlot]) && $kelasSlots[$gene['kelas_id']][$oldSlot] === $b1) {
                            unset($kelasSlots[$gene['kelas_id']][$oldSlot]);
                        }
                    }
                    $chromosome[$b1] = $bestSlot;
                    foreach ($blocks[$b1] as $offset => $geneIdx) {
                        $gene = $genes[$geneIdx];
                        $newSlot = $bestSlot + $offset;
                        $guruSlots[$gene['guru_id']][$newSlot] = $b1;
                        $kelasSlots[$gene['kelas_id']][$newSlot] = $b1;
                    }
                }
            } else {
                // Swap with another block of same size
                $sameSizeBlocks = [];
                foreach ($blocks as $idx => $block) {
                    if (count($block) === $size1 && $idx !== $b1) {
                        $sameSizeBlocks[] = $idx;
                    }
                }
                
                if (!empty($sameSizeBlocks)) {
                    $b2 = $sameSizeBlocks[array_rand($sameSizeBlocks)];
                    $tmp = $chromosome[$b1];
                    $chromosome[$b1] = $chromosome[$b2];
                    $chromosome[$b2] = $tmp;
                } else {
                    $validStarts = $validBlockStarts[$size1];
                    if (!empty($validStarts)) {
                        $chromosome[$b1] = $validStarts[array_rand($validStarts)];
                    }
                }
            }
        }

        return $chromosome;
    }

    private function localSearch(array $chromosome, array $ctx): array
    {
        $blocks = $ctx['blocks'];
        $genes = $ctx['genes'];
        $validBlockStarts = $ctx['validBlockStarts'];
        $eval = $this->evaluate($chromosome, $ctx);
        $bestScore = $eval['total'];
        $bestChromosome = $chromosome;
        $totalBlocks = count($blocks);

        // Phase 1: Fix conflicting blocks first (targeted)
        $conflictingBlocks = $eval['conflicting_blocks'];
        foreach ($conflictingBlocks as $b1) {
            $size1 = count($blocks[$b1]);
            $validStarts = $validBlockStarts[$size1];
            // Try every valid position for this conflicting block
            foreach ($validStarts as $startSlot) {
                if ($startSlot === $bestChromosome[$b1]) continue;
                $trial = $bestChromosome;
                $trial[$b1] = $startSlot;
                $trialScore = $this->evaluate($trial, $ctx)['total'];
                if ($trialScore < $bestScore) {
                    $bestScore = $trialScore;
                    $bestChromosome = $trial;
                }
            }
        }

        // Phase 2: Random perturbations for general improvement
        for ($i = 0; $i < 150; $i++) {
            $trial = $bestChromosome;
            // Re-evaluate to find current conflicts
            if ($i % 30 === 0) {
                $eval = $this->evaluate($bestChromosome, $ctx);
                $conflictingBlocks = $eval['conflicting_blocks'];
            }

            // Prefer conflicting blocks
            if (!empty($conflictingBlocks) && $this->randFloat() > 0.3) {
                $b1 = $conflictingBlocks[array_rand($conflictingBlocks)];
            } else {
                $b1 = rand(0, $totalBlocks - 1);
            }

            $size1 = count($blocks[$b1]);
            
            if ($this->randFloat() > 0.4) {
                $validStarts = $validBlockStarts[$size1];
                $trial[$b1] = $validStarts[array_rand($validStarts)];
            } else {
                $b2 = rand(0, $totalBlocks - 1);
                if ($b1 !== $b2 && count($blocks[$b2]) === $size1) {
                    $tmp = $trial[$b1];
                    $trial[$b1] = $trial[$b2];
                    $trial[$b2] = $tmp;
                }
            }
            
            $trialScore = $this->evaluate($trial, $ctx)['total'];
            if ($trialScore < $bestScore) {
                $bestScore = $trialScore;
                $bestChromosome = $trial;
            }
        }
        return $bestChromosome;
    }

    private function classPreservingCrossover(array $p1, array $p2, array $ctx): array
    {
        $c1 = $p1;
        $c2 = $p2;
        
        $blocks = $ctx['blocks'];
        $genes = $ctx['genes'];

        $kelasBlocks = [];
        foreach ($blocks as $idx => $blockGenes) {
            $kelasId = $genes[$blockGenes[0]]['kelas_id'];
            $kelasBlocks[$kelasId][] = $idx;
        }

        foreach ($kelasBlocks as $kelasId => $blockIndices) {
            if ($this->randFloat() > 0.5) {
                foreach ($blockIndices as $idx) {
                    $c1[$idx] = $p2[$idx];
                    $c2[$idx] = $p1[$idx];
                }
            }
        }

        return [$c1, $c2];
    }

    private function tournamentSelect(array $population, array $fitnessValues): array
    {
        $k = min(3, count($population));
        $bestIdx = -1;
        $bestFitness = -1.0;

        for ($i = 0; $i < $k; $i++) {
            $idx = array_rand($population);
            if ($fitnessValues[$idx] > $bestFitness) {
                $bestFitness = $fitnessValues[$idx];
                $bestIdx = $idx;
            }
        }
        return $population[$bestIdx];
    }

    private function randFloat(): float
    {
        return mt_rand() / mt_getrandmax();
    }
}