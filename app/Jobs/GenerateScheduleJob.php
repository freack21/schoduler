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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    // Tuned GA parameters
    private int $populationSize = 50;
    private int $maxGenerations = 300;
    private float $crossoverRate = 0.85; 
    private float $mutationRate = 0.15;  
    private int $eliteCount = 5;         

    public function handle(): void
    {
        Cache::put('ga_status', 'running', 600);
        Cache::put('ga_generation', 0, 600);
        Cache::put('ga_fitness', 0, 600);
        Cache::put('ga_violations', 0, 600);

        $guruMapels = GuruMapel::with(['mapel', 'guru', 'kelas'])->get();
        $jamPelajaranList = JamPelajaran::orderBy('jam_ke')->get();
        $hariAktif = Pengaturan::getHariAktif();

        if ($guruMapels->isEmpty() || $jamPelajaranList->isEmpty()) {
            Cache::put('ga_status', 'error', 600);
            Cache::put('ga_message', 'Data guru_mapel atau jam pelajaran kosong.', 600);
            return;
        }

        // Build gene and block list
        $genes = [];
        $blocks = [];
        $mapelJamPerHari = [];
        $mapelJamPerMinggu = [];
        $geneIdx = 0;
        foreach ($guruMapels as $gm) {
            $jamPerHari = $gm->mapel->jam_per_hari;
            $jam = $gm->mapel->jam_per_minggu;
            $mapelJamPerHari[$gm->mapel_id] = $jamPerHari;
            $mapelJamPerMinggu[$gm->mapel_id] = $jam;
            
            $blockSizes = [];
            while ($jam > 0) {
                $size = min($jam, $jamPerHari);
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

        // Only non-istirahat slots
        $jamAktif = $jamPelajaranList->where('is_istirahat', false);
        $jamIds = $jamAktif->pluck('id')->toArray();

        $blocks = array_values($blocks);
        $totalGenes = count($genes);
        $totalBlocks = count($blocks);

        // Build consecutive position map (skipping istirahat)
        $jamPositionMap = [];
        $pos = 0;
        foreach ($jamPelajaranList as $jp) {
            if (!$jp->is_istirahat) {
                $jamPositionMap[$jp->id] = $pos++;
            }
        }

        // Build slot map
        $slotMap = [];
        foreach ($hariAktif as $hariIdx => $hari) {
            foreach ($jamIds as $jamId) {
                $slotMap[] = [
                    'hari' => $hari,
                    'jam_pelajaran_id' => $jamId,
                    'hari_idx' => $hariIdx,
                    'jam_pos' => $jamPositionMap[$jamId],
                ];
            }
        }
        $totalSlots = count($slotMap);

        if ($totalSlots === 0) {
            Cache::put('ga_status', 'error', 600);
            Cache::put('ga_message', 'Tidak ada slot waktu tersedia.', 600);
            return;
        }

        $slotsPerDay = count($jamIds);
        $totalHari = count($hariAktif);

        Cache::put('ga_max_generations', $this->maxGenerations, 600);

        $bestOverallScore = null;
        $bestOverallHard = null;
        $bestOverallDist = null;
        $bestOverallFitness = null;
        Cache::put('ga_best_generation', 0, 600);

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
                    $validBlockStarts[$size][] = $s;
                }
            }
        }

        $evalContext = [
            'genes' => $genes,
            'blocks' => $blocks,
            'slotMap' => $slotMap,
            'mapelJamPerHari' => $mapelJamPerHari,
            'mapelJamPerMinggu' => $mapelJamPerMinggu,
            'kelasAllowedSlots' => $kelasAllowedSlots,
            'validBlockStarts' => $validBlockStarts,
            'totalHari' => $totalHari,
            'slotsPerDay' => $slotsPerDay,
        ];

        // ── INITIAL POPULATION (Block Based) ──
        $population = [];
        $population[] = $this->createSmartChromosome($evalContext);

        $smartCount = min(20, max(5, (int) round($this->populationSize * 0.25)));
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
                
                Cache::put('ga_best_generation', $gen, 600);
                Cache::put('ga_best_hard', $bestOverallHard, 600);
                Cache::put('ga_best_dist', $bestOverallDist, 600);
                Cache::put('ga_violations', $bestOverallHard, 600);
                Cache::put('ga_dist_violations', $bestOverallDist, 600);
                Cache::put('ga_fitness', round($bestOverallFitness, 6), 600);
            }

            Cache::put('ga_generation', $gen + 1, 600);
            Cache::put('ga_fitness', round(1.0 / (1.0 + $currentBestScore), 6), 600);

            if ($bestScore === 0) {
                Cache::put('ga_generation', $gen + 1, 600);
                Cache::put('ga_fitness', 1.0, 600);
                break;
            }

            // Local search
            if ($gen > 0 && $gen % 10 === 0 && $bestChromosome) {
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
                $keepCount = (int) ceil($this->populationSize * 0.20);
                $newPop = [];
                for ($i = 0; $i < $keepCount; $i++) {
                    $newPop[] = $indexed[$i]['c'];
                }
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
            : ['guru_conflicts' => -1, 'kelas_conflicts' => -1, 'dist_violations' => -1, 'consecutive_violations' => -1, 'total' => -1, 'day_priority_penalty' => -1];

        $hard = $final['guru_conflicts'] + $final['kelas_conflicts'];

        Cache::put('ga_status', 'done', 600);
        Cache::put('ga_fitness', round(1.0 / (1.0 + $final['total']), 6), 600);
        Cache::put('ga_violations', $hard, 600);
        Cache::put('ga_dist_violations', $final['dist_violations'], 600);

        Log::info("GA DONE: Score={$final['total']}, Hard={$hard}, Dist={$final['dist_violations']}, Cons={$final['consecutive_violations']}");

        if ($hard === 0 && $final['dist_violations'] === 0 && $final['consecutive_violations'] === 0) {
            Cache::put('ga_message', 'Jadwal berhasil digenerate tanpa bentrok, blok rapi! 🎯', 600);
        } elseif ($hard === 0) {
            Cache::put('ga_message', "Jadwal tanpa bentrok! Soft: {$final['dist_violations']} distribusi, {$final['consecutive_violations']} urutan.", 600);
        } else {
            Cache::put('ga_message', "Jadwal digenerate dengan {$hard} bentrok. Perlu di-generate ulang.", 600);
        }
    }

    private function createSmartChromosome(array $ctx): array
    {
        $blocks = $ctx['blocks'];
        $genes = $ctx['genes'];
        $validBlockStarts = $ctx['validBlockStarts'];
        
        $totalBlocks = count($blocks);
        $chromosome = array_fill(0, $totalBlocks, 0);
        $usedGuruSlots = [];
        $usedKelasSlots = [];

        $indices = range(0, $totalBlocks - 1);
        // Sort blocks by size descending (largest blocks first)
        usort($indices, fn($a, $b) => count($blocks[$b]) <=> count($blocks[$a]));

        foreach ($indices as $b) {
            $blockSize = count($blocks[$b]);
            $firstGene = $genes[$blocks[$b][0]];
            $guruId = $firstGene['guru_id'];
            $kelasId = $firstGene['kelas_id'];
            
            $validStarts = $validBlockStarts[$blockSize];
            
            $bestSlot = -1;
            $bestScore = -PHP_INT_MAX;

            foreach ($validStarts as $s) {
                // Check if all slots in the block are free
                $conflict = false;
                for ($offset = 0; $offset < $blockSize; $offset++) {
                    if (isset($usedGuruSlots[$guruId][$s + $offset]) || isset($usedKelasSlots[$kelasId][$s + $offset])) {
                        $conflict = true;
                        break;
                    }
                }
                
                if ($conflict) continue;

                $score = count($validStarts) - $s; // prefer earlier
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestSlot = $s;
                }
            }

            if ($bestSlot === -1) {
                $bestSlot = $validStarts[array_rand($validStarts)];
            }

            $chromosome[$b] = $bestSlot;
            for ($offset = 0; $offset < $blockSize; $offset++) {
                $usedGuruSlots[$guruId][$bestSlot + $offset] = true;
                $usedKelasSlots[$kelasId][$bestSlot + $offset] = true;
            }
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
                
                // Day priority: slight penalty for later days
                $dayPriorityPenalty += $hariIdx * 0.1;
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
                $distViolations += ($actualDays - $idealDays) * 5; // heavy penalty for spread
            }

            foreach ($hariData as $hariIdx => $positions) {
                $count = count($positions);
                if ($count > $jamPerHari) {
                    $distViolations += ($count - $jamPerHari) * 2;
                }
                // Consecutive: already guaranteed within blocks, but check across blocks on same day
                if ($count > 1) {
                    sort($positions);
                    for ($i = 1; $i < $count; $i++) {
                        if ($positions[$i] !== $positions[$i - 1] + 1) {
                            $consecutiveViolations += 5; // heavy penalty for non-consecutive
                        }
                    }
                }
            }
        }

        $hardScore = ($guruConflicts * 1000) + ($kelasConflicts * 1000);
        $softScore = ($distViolations * 10) + ($consecutiveViolations * 5) + $dayPriorityPenalty;
        $total = $hardScore + $softScore;

        return [
            'guru_conflicts' => $guruConflicts,
            'kelas_conflicts' => $kelasConflicts,
            'dist_violations' => $distViolations,
            'consecutive_violations' => $consecutiveViolations,
            'day_priority_penalty' => $dayPriorityPenalty,
            'total' => $total,
            'conflicting_blocks' => array_keys($conflictingBlocks),
        ];
    }

        private function smartMutate(array $chromosome, array $ctx, int $totalSlots): array
    {
        if ($this->randFloat() > $this->mutationRate) {
            return $chromosome;
        }

        $blocks = $ctx['blocks'];
        $validBlockStarts = $ctx['validBlockStarts'];
        $totalBlocks = count($blocks);
        
        $eval = $this->evaluate($chromosome, $ctx);
        $conflictingBlocks = $eval['conflicting_blocks'];
        
        $numMutations = rand(1, 5);

        for ($m = 0; $m < $numMutations; $m++) {
            // Target conflicting blocks preferentially
            if (!empty($conflictingBlocks) && $this->randFloat() > 0.3) {
                $b1 = $conflictingBlocks[array_rand($conflictingBlocks)];
            } else {
                $b1 = rand(0, $totalBlocks - 1);
            }
            
            $size1 = count($blocks[$b1]);
            
            if ($this->randFloat() > 0.5) {
                $validStarts = $validBlockStarts[$size1];
                $chromosome[$b1] = $validStarts[array_rand($validStarts)];
            } else {
                $b2 = rand(0, $totalBlocks - 1);
                if ($b1 !== $b2 && count($blocks[$b2]) === $size1) {
                    $tmp = $chromosome[$b1];
                    $chromosome[$b1] = $chromosome[$b2];
                    $chromosome[$b2] = $tmp;
                }
            }
        }

        return $chromosome;
    }

    private function localSearch(array $chromosome, array $ctx): array
    {
        $blocks = $ctx['blocks'];
        $validBlockStarts = $ctx['validBlockStarts'];
        $bestScore = $this->evaluate($chromosome, $ctx)['total'];
        $bestChromosome = $chromosome;
        $totalBlocks = count($blocks);

        for ($i = 0; $i < 50; $i++) {
            $trial = $bestChromosome;
            $b1 = rand(0, $totalBlocks - 1);
            $size1 = count($blocks[$b1]);
            
            if ($this->randFloat() > 0.5) {
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