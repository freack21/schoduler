<?php

namespace App\Jobs;

use App\Models\Jadwal;
use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\GuruMapel;
use App\Models\ScheduleGeneration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900;

    // GA parameters
    private int $populationSize = 100;
    private int $maxGenerations = 600;
    private float $crossoverRate = 0.85; 
    private float $mutationRate = 0.25;  
    private int $eliteCount = 8;         
    
    private int $scheduleGenerationId;

    public function __construct(int $scheduleGenerationId)
    {
        $this->scheduleGenerationId = $scheduleGenerationId;
    }

    public function handle(): void
    {
        $genState = ScheduleGeneration::find($this->scheduleGenerationId);
        if (!$genState) return;
        
        $genState->update(['status' => 'running', 'message' => 'Memulai inisialisasi data...']);

        // ── LOAD DATA ──
        $jamList = JamPelajaran::all();
        $allDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $dbDays = $jamList->pluck('hari')->unique()->toArray();
        $hariAktif = array_values(array_intersect($allDays, $dbDays));

        $slotMap = [];
        $s = 0;
        foreach ($hariAktif as $hIdx => $hari) {
            $jamsForHari = $jamList->where('hari', trim($hari))->sortBy('jam_ke');
            $jIdx = 0;
            foreach ($jamsForHari as $jam) {
                if ($jam->is_istirahat) continue;
                
                $slotMap[$s] = [
                    'hari' => trim($hari),
                    'hari_idx' => $hIdx,
                    'jam_ke' => $jam->jam_ke,
                    'jam_pos' => $jIdx,
                    'jam_pelajaran_id' => $jam->id,
                ];
                $s++;
                $jIdx++;
            }
        }
        $totalSlots = count($slotMap);

        // PRECOMPUTE VALID BLOCK STARTS
        $validBlockStarts = [];
        for ($size = 1; $size <= 4; $size++) {
            $validBlockStarts[$size] = [];
            for ($slot = 0; $slot <= $totalSlots - $size; $slot++) {
                $startHari = $slotMap[$slot]['hari_idx'];
                $endHari = $slotMap[$slot + $size - 1]['hari_idx'];
                if ($startHari === $endHari) {
                    $validBlockStarts[$size][] = $slot;
                }
            }
            if (empty($validBlockStarts[$size])) {
                $validBlockStarts[$size] = $validBlockStarts[1] ?? [];
            }
        }

        // ── LOAD DEMANDS ──
        $demands = [];
        $kelasList = Kelas::with(['jurusan', 'tingkat'])->get();
        $kurikulumList = Kurikulum::with('mapel')->get();
        $guruMapelAll = GuruMapel::all();

        foreach ($kelasList as $k) {
            $kuriList = $kurikulumList->where('tingkat_id', $k->tingkat_id);
            if ($k->jurusan_id) {
                $kuriList = $kuriList->filter(function($kuri) use ($k) {
                    return is_null($kuri->jurusan_id) || $kuri->jurusan_id == $k->jurusan_id;
                });
            } else {
                $kuriList = $kuriList->whereNull('jurusan_id');
            }

            $kelasDemands = [];
            foreach ($kuriList as $kuri) {
                $eligibleGurus = $guruMapelAll->where('mapel_id', $kuri->mapel_id)
                    ->where('tingkat_id', $k->tingkat_id)
                    ->filter(function($gm) use ($k) {
                        return is_null($gm->jurusan_id) || $gm->jurusan_id == $k->jurusan_id;
                    })
                    ->pluck('guru_id')
                    ->values()
                    ->toArray();

                if (empty($eligibleGurus)) continue;
                
                if ($kuri->mapel->is_parallel) {
                    $kelompok = $kuri->mapel->kelompok_paralel ?: ('id_' . $kuri->mapel->id);
                    $key = 'parallel_' . md5($kelompok) . '_' . $kuri->mapel->jam_per_minggu . '_' . $kuri->mapel->jam_per_hari;

                    if (!isset($kelasDemands[$key])) {
                        $kelasDemands[$key] = [
                            'kelas_id' => $k->id,
                            'mapel_ids' => [],
                            'is_parallel' => true,
                            'jam_per_minggu' => $kuri->mapel->jam_per_minggu,
                            'jam_per_hari' => $kuri->mapel->jam_per_hari,
                            'eligible_gurus' => []
                        ];
                    }
                    
                    if (!in_array($kuri->mapel_id, $kelasDemands[$key]['mapel_ids'])) {
                        $kelasDemands[$key]['mapel_ids'][] = $kuri->mapel_id;
                        $kelasDemands[$key]['eligible_gurus'][$kuri->mapel_id] = array_values($eligibleGurus);
                    }
                } else {
                    $demands[] = [
                        'kelas_id' => $k->id,
                        'mapel_ids' => [$kuri->mapel_id],
                        'is_parallel' => false,
                        'jam_per_minggu' => $kuri->mapel->jam_per_minggu,
                        'jam_per_hari' => $kuri->mapel->jam_per_hari,
                        'eligible_gurus' => [
                            $kuri->mapel_id => array_values($eligibleGurus)
                        ]
                    ];
                }
            }
            
            foreach ($kelasDemands as $d) {
                $demands[] = $d;
            }
        }

        // ── GENERATE BLOCKS ──
        $blocks = [];
        $demandBlocks = [];
        foreach ($demands as $dIdx => $demand) {
            $sisa = $demand['jam_per_minggu'];
            $maxPerHari = $demand['jam_per_hari'];
            
            while ($sisa > 0) {
                // If maxPerHari is empty or 0, default to sisa to not split
                $maxPH = (empty($maxPerHari) || $maxPerHari <= 0) ? $sisa : $maxPerHari;
                $take = min($sisa, $maxPH);
                $bIdx = count($blocks);
                $blocks[] = [
                    'demand_idx' => $dIdx,
                    'size' => $take
                ];
                $demandBlocks[$dIdx][] = $bIdx;
                $sisa -= $take;
            }
        }

        $evalContext = [
            'demands' => $demands,
            'blocks' => $blocks,
            'demandBlocks' => $demandBlocks,
            'slotMap' => $slotMap,
            'validBlockStarts' => $validBlockStarts,
        ];

        // ── INITIAL POPULATION ──
        $population = [];
        $smartCount = max(10, (int) round($this->populationSize * 0.60));
        
        for ($i = 0; $i < $this->populationSize; $i++) {
            if ($i < $smartCount) {
                $population[] = $this->createSmartChromosome($evalContext);
            } else {
                $population[] = $this->createRandomChromosome($evalContext);
            }
        }

        $bestChromosome = null;
        $bestScore = PHP_INT_MAX;
        $stagnantGenerations = 0;
        $lastBestScore = PHP_INT_MAX;

        // ── EVOLUTION LOOP ──
        for ($gen = 0; $gen < $this->maxGenerations; $gen++) {
            $fitnessValues = [];
            $indexed = [];
            
            foreach ($population as $idx => $chromosome) {
                $eval = $this->evaluate($chromosome, $evalContext);
                $score = $eval['total'];
                $fitness = 1.0 / (1.0 + $score);
                $fitnessValues[$idx] = $fitness;
                $indexed[] = ['c' => $chromosome, 'f' => $fitness, 's' => $score, 'idx' => $idx];
                if ($gen === 0) {
                    if ($idx === 0) {
                        \Illuminate\Support\Facades\Log::info("FIRST RANDOM CHROM EVAL: " . json_encode($eval));
                    }
                    if ($idx === (int)($this->populationSize * 0.3)) {
                        \Illuminate\Support\Facades\Log::info("FIRST SMART CHROM EVAL: " . json_encode($eval));
                        file_put_contents(storage_path('demands_job.json'), json_encode($evalContext['demands'], JSON_PRETTY_PRINT));
                        file_put_contents(storage_path('blocks_job.json'), json_encode($evalContext['blocks'], JSON_PRETTY_PRINT));
                    }
                }
                
                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestChromosome = $chromosome;
                }
            }

            usort($indexed, fn($a, $b) => $b['f'] <=> $a['f']);

            if ($gen === 0) {
                \Illuminate\Support\Facades\Log::info("BEST CHROM EVAL (GEN 0): " . json_encode($this->evaluate($indexed[0]['c'], $evalContext)));
            }

            if ($gen % 10 === 0) {
                $bestEval = $this->evaluate($bestChromosome, $evalContext);
                $hard = $bestEval['guru_conflicts'] + $bestEval['kelas_conflicts'] + $bestEval['same_day_mapel'];
                $genState->update([
                    'generation' => $gen + 1,
                    'fitness' => $indexed[0]['f'],
                    'violations' => $hard,
                    'dist_violations' => $bestEval['dist_violations'],
                    'message' => "Evolusi generasi " . ($gen + 1) . "... (Hard: {$hard}, Packing: {$bestEval['packing_penalty']})",
                ]);
            }

            if ($bestScore === 0) break;

            if ($gen > 0 && $gen % 5 === 0 && $bestChromosome) {
                $improved = $this->localSearch($bestChromosome, $evalContext);
                $improvedScore = $this->evaluate($improved, $evalContext)['total'];
                if ($improvedScore < $bestScore) {
                    $bestScore = $improvedScore;
                    $bestChromosome = $improved;
                    $population[0] = $bestChromosome;
                }
            }

            $newPop = [];
            for ($i = 0; $i < $this->eliteCount; $i++) {
                $newPop[] = $indexed[$i]['c'];
            }

            while (count($newPop) < $this->populationSize) {
                $p1 = $this->tournamentSelect($population, $fitnessValues);
                $p2 = $this->tournamentSelect($population, $fitnessValues);
                [$c1, $c2] = $this->crossover($p1, $p2, $evalContext);
                $c1 = $this->mutate($c1, $evalContext);
                $c2 = $this->mutate($c2, $evalContext);
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
                $freshCount = (int)(($this->populationSize - $keepCount) * 0.5);
                for ($i = 0; $i < $freshCount; $i++) {
                    $newPop[] = $this->createSmartChromosome($evalContext);
                }
                while (count($newPop) < $this->populationSize) {
                    $p1 = $this->tournamentSelect($population, $fitnessValues);
                    $p2 = $this->tournamentSelect($population, $fitnessValues);
                    [$c1, $c2] = $this->crossover($p1, $p2, $evalContext);
                    $c1 = $this->mutate($c1, $evalContext);
                    $c2 = $this->mutate($c2, $evalContext);
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
        if ($bestChromosome) {
            $repairRounds = 0;
            while ($repairRounds < 150) {
                $eval = $this->evaluate($bestChromosome, $evalContext);
                $hardConflicts = $eval['guru_conflicts'] + $eval['kelas_conflicts'] + $eval['same_day_mapel'];
                if ($hardConflicts === 0) break;
                
                $conflictingBlocks = $eval['conflicting_blocks'];
                if (empty($conflictingBlocks) && $hardConflicts > 0) {
                    // if conflicts exist but not registered in conflicting blocks (e.g. same day mapel)
                    $conflictingBlocks = array_keys($evalContext['blocks']);
                    shuffle($conflictingBlocks);
                    $conflictingBlocks = array_slice($conflictingBlocks, 0, 20);
                }
                
                if (empty($conflictingBlocks)) break;
                
                $improved = false;
                foreach ($conflictingBlocks as $b) {
                    $blockSize = $evalContext['blocks'][$b]['size'];
                    $validStarts = $evalContext['validBlockStarts'][$blockSize];
                    $currentScore = $eval['total'];
                    
                    $bestTrial = null;
                    $bestNewScore = $currentScore;
                    
                    // Try changing guru if possible
                    $dIdx = $evalContext['blocks'][$b]['demand_idx'];
                    $demand = $evalContext['demands'][$dIdx];
                    foreach ($demand['eligible_gurus'] as $mId => $eligibleGurus) {
                        if (count($eligibleGurus) > 1) {
                            foreach ($eligibleGurus as $g) {
                                if ($g === $bestChromosome['gurus'][$dIdx][$mId]) continue;
                                $trial = $bestChromosome;
                                $trial['gurus'][$dIdx][$mId] = $g;
                                $trialEval = $this->evaluate($trial, $evalContext);
                                if ($trialEval['total'] < $bestNewScore) {
                                    $bestNewScore = $trialEval['total'];
                                    $bestTrial = $trial;
                                    $improved = true;
                                }
                            }
                        }
                    }

                    // Shuffle starts for randomness in repair
                    $validStarts = $evalContext['validBlockStarts'][$blockSize];
                    shuffle($validStarts);
                    $sampledStarts = array_slice($validStarts, 0, 100);
                    
                    foreach ($sampledStarts as $s) {
                        if ($s === $bestChromosome['slots'][$b]) continue;
                        $trial = $bestChromosome;
                        $trial['slots'][$b] = $s;
                        $trialEval = $this->evaluate($trial, $evalContext);
                        if ($trialEval['total'] < $bestNewScore) {
                            $bestNewScore = $trialEval['total'];
                            $bestTrial = $trial;
                            $improved = true;
                        }
                    }
                    
                    // Add Swap Logic (Swap within the same class to preserve class schedule validity)
                    $bKelasId = $evalContext['demands'][$evalContext['blocks'][$b]['demand_idx']]['kelas_id'];
                    $sameSizeBlocks = array_keys(array_filter($evalContext['blocks'], function($x) use ($blockSize, $bKelasId, $evalContext) {
                        return $x['size'] === $blockSize && $evalContext['demands'][$x['demand_idx']]['kelas_id'] === $bKelasId;
                    }));
                    shuffle($sameSizeBlocks);
                    $sampledBlocks = array_slice($sameSizeBlocks, 0, 50);
                    
                    foreach ($sampledBlocks as $otherBIdx) {
                        if ($b === $otherBIdx) continue;
                        $trial = $bestChromosome;
                        $temp = $trial['slots'][$b];
                        $trial['slots'][$b] = $trial['slots'][$otherBIdx];
                        $trial['slots'][$otherBIdx] = $temp;
                        $trialEval = $this->evaluate($trial, $evalContext);
                        if ($trialEval['total'] < $bestNewScore) {
                            $bestNewScore = $trialEval['total'];
                            $bestTrial = $trial;
                            $improved = true;
                        }
                    }
                    
                    if ($bestTrial) {
                        $bestChromosome = $bestTrial;
                        $bestScore = $bestNewScore;
                    }
                }
                
                if (!$improved) break;
                $repairRounds++;
            }
        }

        // ── SAVE RESULT ──
        Jadwal::truncate();

        if ($bestChromosome) {
            $entries = [];
            $savedKelasSlots = [];
            $savedGuruSlots = [];
            
            foreach ($bestChromosome['slots'] as $bIdx => $startSlot) {
                $block = $evalContext['blocks'][$bIdx];
                $dIdx = $block['demand_idx'];
                $demand = $evalContext['demands'][$dIdx];
                $guruMap = $bestChromosome['gurus'][$dIdx];
                $kelasId = $demand['kelas_id'];
                
                $canSave = true;
                for ($i = 0; $i < $block['size']; $i++) {
                    $sIdx = $startSlot + $i;
                    if (isset($savedKelasSlots[$kelasId][$sIdx])) {
                        $canSave = false;
                        break;
                    }
                    foreach ($guruMap as $guruId) {
                        if (isset($savedGuruSlots[$guruId][$sIdx])) {
                            $canSave = false;
                            break 2;
                        }
                    }
                }
                
                if ($canSave) {
                    for ($i = 0; $i < $block['size']; $i++) {
                        $sIdx = $startSlot + $i;
                        $savedKelasSlots[$kelasId][$sIdx] = true;
                        
                        $slot = $slotMap[$sIdx];
                        foreach ($guruMap as $mId => $guruId) {
                            $savedGuruSlots[$guruId][$sIdx] = true;
                            
                            $entries[] = [
                                'guru_id' => $guruId,
                                'mapel_id' => $mId,
                                'kelas_id' => $demand['kelas_id'],
                                'hari' => $slot['hari'],
                                'jam_pelajaran_id' => $slot['jam_pelajaran_id'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }
            }
            // Chunk inserts
            $chunks = array_chunk($entries, 200);
            foreach ($chunks as $chunk) {
                Jadwal::insert($chunk);
            }
        }

        $final = $bestChromosome ? $this->evaluate($bestChromosome, $evalContext) : null;
        if ($final) {
            $hard = $final['guru_conflicts'] + $final['kelas_conflicts'] + $final['same_day_mapel'];
            $finalFitness = round(1.0 / (1.0 + $final['total']), 6);
            $genState->update([
                'status' => 'done',
                'fitness' => $finalFitness,
                'violations' => $hard,
                'dist_violations' => $final['dist_violations'],
                'message' => $hard > 0 ? "Jadwal digenerate dengan {$hard} bentrok. Perlu di-generate ulang." : "Jadwal berhasil digenerate tanpa bentrok keras dan penuh di hari awal!",
            ]);
        } else {
            $genState->update(['status' => 'failed', 'message' => 'Gagal menghasilkan jadwal']);
        }
    }

    private function createRandomChromosome(array $ctx): array
    {
        $demands = $ctx['demands'];
        $blocks = $ctx['blocks'];
        $validBlockStarts = $ctx['validBlockStarts'];
        
        $gurus = [];
        $slots = [];

        foreach ($demands as $dIdx => $demand) {
            $picked = [];
            foreach ($demand['eligible_gurus'] as $mId => $eligibleList) {
                $picked[$mId] = $eligibleList[array_rand($eligibleList)];
            }
            $gurus[$dIdx] = $picked;
        }

        foreach ($blocks as $bIdx => $block) {
            $size = $block['size'];
            $validStarts = $validBlockStarts[$size];
            $slots[$bIdx] = $validStarts[array_rand($validStarts)];
        }

        return ['gurus' => $gurus, 'slots' => $slots];
    }

    private function createSmartChromosome(array $ctx): array
    {
        $demands = $ctx['demands'];
        $blocks = $ctx['blocks'];
        $validBlockStarts = $ctx['validBlockStarts'];
        $slotMap = $ctx['slotMap'];
        
        $gurus = [];
        $slots = [];
        $guruLoad = [];
        
        // 1. Assign Gurus (Load Balancing)
        $dIndices = array_keys($demands);
        usort($dIndices, fn($a, $b) => array_sum(array_map('count', $demands[$a]['eligible_gurus'])) <=> array_sum(array_map('count', $demands[$b]['eligible_gurus'])));

        foreach ($dIndices as $dIdx) {
            $demand = $demands[$dIdx];
            $picked = [];
            foreach ($demand['eligible_gurus'] as $mId => $eligible) {
                $bestGuru = $eligible[0];
                $minLoad = PHP_INT_MAX;
                foreach ($eligible as $gid) {
                    $load = $guruLoad[$gid] ?? 0;
                    if ($load < $minLoad) {
                        $minLoad = $load;
                        $bestGuru = $gid;
                    }
                }
                $picked[$mId] = $bestGuru;
                $guruLoad[$bestGuru] = ($guruLoad[$bestGuru] ?? 0) + $demand['jam_per_minggu'];
            }
            $gurus[$dIdx] = $picked;
        }

        // 2. Assign Slots (Minimize Conflicts)
        $usedGuruSlots = [];
        $usedKelasSlots = [];
        $usedKelasMapelDay = [];
        
        $bIndices = array_keys($blocks);
        usort($bIndices, fn($a, $b) => $blocks[$b]['size'] <=> $blocks[$a]['size']);

        foreach ($bIndices as $bIdx) {
            $block = $blocks[$bIdx];
            $dIdx = $block['demand_idx'];
            $demand = $demands[$dIdx];
            $guruMap = $gurus[$dIdx];
            $kelasId = $demand['kelas_id'];
            $size = $block['size'];
            
            $validStarts = $validBlockStarts[$size];
            // Randomly sort or shuffle to preserve genetic diversity while packing
            if (rand(0, 100) < 70) {
                sort($validStarts);
            } else {
                shuffle($validStarts);
            }
            
            $bestSlot = $validStarts[0] ?? 0;
            $minConflicts = PHP_INT_MAX;
            
            foreach ($validStarts as $s) {
                $conflicts = 0;
                for ($i = 0; $i < $size; $i++) {
                    $sIdx = $s + $i;
                    foreach ($guruMap as $guruId) {
                        if (isset($usedGuruSlots[$guruId][$sIdx])) $conflicts++;
                    }
                    if (isset($usedKelasSlots[$kelasId][$sIdx])) $conflicts++;
                }
                
                $dayIdx = $slotMap[$s]['hari_idx'];
                foreach ($demand['mapel_ids'] as $mapelId) {
                    if (isset($usedKelasMapelDay[$kelasId][$dayIdx][$mapelId])) {
                        $conflicts += 100; // Extremely high penalty to avoid same day mapel during init
                    }
                }
                
                if ($conflicts === 0) {
                    $bestSlot = $s;
                    break;
                }
                
                if ($conflicts < $minConflicts) {
                    $minConflicts = $conflicts;
                    $bestSlot = $s;
                }
            }

            $slots[$bIdx] = $bestSlot;
            $bestDayIdx = $slotMap[$bestSlot]['hari_idx'];
            foreach ($demand['mapel_ids'] as $mapelId) {
                $usedKelasMapelDay[$kelasId][$bestDayIdx][$mapelId] = true;
            }
            
            for ($i = 0; $i < $size; $i++) {
                foreach ($guruMap as $guruId) {
                    $usedGuruSlots[$guruId][$bestSlot + $i] = true;
                }
                $usedKelasSlots[$kelasId][$bestSlot + $i] = true;
            }
        }

        return ['gurus' => $gurus, 'slots' => $slots];
    }

    private function evaluate(array $chromosome, array $ctx): array
    {
        $demands = $ctx['demands'];
        $blocks = $ctx['blocks'];
        $slotMap = $ctx['slotMap'];
        
        $guruSlots = [];
        $kelasSlots = [];
        $guruConflicts = 0;
        $kelasConflicts = 0;
        $conflictingBlocks = [];
        
        $guruDailyLoad = [];
        $kelasDailySlots = [];
        $frontLoadPenalty = 0;
        $kelasMapelDay = [];
        $sameDayMapelPenalty = 0;
        
        $maxSIdx = [];
        $totalSlotsUsed = [];

        foreach ($blocks as $bIdx => $block) {
            $dIdx = $block['demand_idx'];
            $demand = $demands[$dIdx];
            $guruMap = $chromosome['gurus'][$dIdx];
            $kelasId = $demand['kelas_id'];
            $start = $chromosome['slots'][$bIdx];
            $size = $block['size'];
            
            $hasConflict = false;
            for ($i = 0; $i < $size; $i++) {
                $sIdx = $start + $i;
                foreach ($guruMap as $guruId) {
                    if (isset($guruSlots[$guruId][$sIdx])) {
                        $guruConflicts++;
                        $hasConflict = true;
                    }
                    $guruSlots[$guruId][$sIdx] = true;
                }
                
                if (isset($kelasSlots[$kelasId][$sIdx])) {
                    $kelasConflicts++;
                    $hasConflict = true;
                }
                $kelasSlots[$kelasId][$sIdx] = true;

                $dayIdx = $slotMap[$sIdx]['hari_idx'];
                $kelasDailySlots[$kelasId][$dayIdx][] = $sIdx;
                $frontLoadPenalty += $dayIdx;
                
                if (!isset($maxSIdx[$kelasId]) || $sIdx > $maxSIdx[$kelasId]) {
                    $maxSIdx[$kelasId] = $sIdx;
                }
                $totalSlotsUsed[$kelasId] = ($totalSlotsUsed[$kelasId] ?? 0) + 1;
            }
            if ($hasConflict) {
                $conflictingBlocks[$bIdx] = true;
            }

            $dayIdx = $slotMap[$start]['hari_idx'];
            foreach ($guruMap as $guruId) {
                $guruDailyLoad[$guruId][$dayIdx] = ($guruDailyLoad[$guruId][$dayIdx] ?? 0) + $size;
            }
            
            foreach ($demand['mapel_ids'] as $mapelId) {
                if (isset($kelasMapelDay[$kelasId][$dayIdx][$mapelId])) {
                    $sameDayMapelPenalty++;
                    $conflictingBlocks[$bIdx] = true;
                }
                $kelasMapelDay[$kelasId][$dayIdx][$mapelId] = true;
            }
        }

        $packingPenalty = 0;
        foreach ($maxSIdx as $kId => $maxS) {
            $used = $totalSlotsUsed[$kId] ?? 0;
            if ($used > 0) {
                $packingPenalty += ($maxS - $used + 1);
            }
        }

        $distViolations = 0;
        foreach ($guruDailyLoad as $gid => $days) {
            foreach ($days as $load) {
                if ($load > 6) {
                    $distViolations += ($load - 6);
                }
            }
        }

        $gapPenalties = 0;
        foreach ($kelasDailySlots as $kId => $days) {
            foreach ($days as $dIdx => $slots) {
                sort($slots);
                $count = count($slots);
                if ($count > 0) {
                    $startOfDayIdx = $slots[0] - $slotMap[$slots[0]]['jam_pos'];
                    $gapPenalties += ($slots[0] - $startOfDayIdx);
                    
                    for ($i = 1; $i < $count; $i++) {
                        $diff = $slots[$i] - $slots[$i - 1];
                        if ($diff > 1) {
                            $gapPenalties += ($diff - 1) * 3;
                        }
                    }
                }
            }
        }

        $total = ($guruConflicts * 1000000) 
               + ($kelasConflicts * 1000000) 
               + ($sameDayMapelPenalty * 50000)
               + ($distViolations * 100) 
               + ($gapPenalties * 10) 
               + ($packingPenalty * 5)
               + ($frontLoadPenalty * 1);

        return [
            'guru_conflicts' => $guruConflicts,
            'kelas_conflicts' => $kelasConflicts,
            'same_day_mapel' => $sameDayMapelPenalty,
            'dist_violations' => $distViolations,
            'packing_penalty' => $packingPenalty,
            'total' => $total,
            'conflicting_blocks' => array_keys($conflictingBlocks),
        ];
    }

    private function tournamentSelect(array $population, array $fitnessValues): array
    {
        $bestIdx = rand(0, count($population) - 1);
        for ($i = 1; $i < 4; $i++) {
            $idx = rand(0, count($population) - 1);
            if ($fitnessValues[$idx] > $fitnessValues[$bestIdx]) {
                $bestIdx = $idx;
            }
        }
        return $population[$bestIdx];
    }

    private function crossover(array $p1, array $p2, array $ctx): array
    {
        if ($this->randFloat() > $this->crossoverRate) {
            return [$p1, $p2];
        }

        $c1 = ['gurus' => [], 'slots' => []];
        $c2 = ['gurus' => [], 'slots' => []];

        foreach ($p1['gurus'] as $dIdx => $gid) {
            if (rand(0, 1)) {
                $c1['gurus'][$dIdx] = $p1['gurus'][$dIdx];
                $c2['gurus'][$dIdx] = $p2['gurus'][$dIdx];
            } else {
                $c1['gurus'][$dIdx] = $p2['gurus'][$dIdx];
                $c2['gurus'][$dIdx] = $p1['gurus'][$dIdx];
            }
        }

        foreach ($p1['slots'] as $bIdx => $slot) {
            if (rand(0, 1)) {
                $c1['slots'][$bIdx] = $p1['slots'][$bIdx];
                $c2['slots'][$bIdx] = $p2['slots'][$bIdx];
            } else {
                $c1['slots'][$bIdx] = $p2['slots'][$bIdx];
                $c2['slots'][$bIdx] = $p1['slots'][$bIdx];
            }
        }

        return [$c1, $c2];
    }

    private function mutate(array $chromosome, array $ctx): array
    {
        if ($this->randFloat() > $this->mutationRate) {
            return $chromosome;
        }

        $demands = $ctx['demands'];
        $blocks = $ctx['blocks'];
        $validBlockStarts = $ctx['validBlockStarts'];
        
        $eval = $this->evaluate($chromosome, $ctx);
        $conflictingBlocks = $eval['conflicting_blocks'];
        
        $numMutations = rand(1, 4);

        for ($m = 0; $m < $numMutations; $m++) {
            // Decide whether to mutate Guru or Slot
            if (rand(0, 1) === 0) {
                // Mutate Guru
                $dIdx = array_rand($demands);
                if (!empty($conflictingBlocks) && rand(0, 1) === 0) {
                    $bIdx = $conflictingBlocks[array_rand($conflictingBlocks)];
                    $dIdx = $blocks[$bIdx]['demand_idx'];
                }
                $eligibleMap = $demands[$dIdx]['eligible_gurus'];
                $mIdToMutate = array_rand($eligibleMap);
                $eligible = $eligibleMap[$mIdToMutate];
                if (count($eligible) > 1) {
                    $chromosome['gurus'][$dIdx][$mIdToMutate] = $eligible[array_rand($eligible)];
                }
            } else {
                // Mutate Slot
                $bIdx = array_rand($blocks);
                if (!empty($conflictingBlocks) && rand(0, 1) === 0) {
                    $bIdx = $conflictingBlocks[array_rand($conflictingBlocks)];
                }
                $size = $blocks[$bIdx]['size'];
                $validStarts = $validBlockStarts[$size];
                if (!empty($validStarts)) {
                    $chromosome['slots'][$bIdx] = $validStarts[array_rand($validStarts)];
                }
            }
        }

        return $chromosome;
    }

    private function localSearch(array $chromosome, array $ctx): array
    {
        $blocks = $ctx['blocks'];
        $validBlockStarts = $ctx['validBlockStarts'];
        
        $eval = $this->evaluate($chromosome, $ctx);
        $bestScore = $eval['total'];
        $bestChromosome = $chromosome;
        
        $conflictingBlocks = $eval['conflicting_blocks'];
        foreach ($conflictingBlocks as $bIdx) {
            $size = $blocks[$bIdx]['size'];
            $validStarts = $validBlockStarts[$size];
            
            foreach ($validStarts as $s) {
                if ($s === $bestChromosome['slots'][$bIdx]) continue;
                $trial = $bestChromosome;
                $trial['slots'][$bIdx] = $s;
                $trialScore = $this->evaluate($trial, $ctx)['total'];
                if ($trialScore < $bestScore) {
                    $bestScore = $trialScore;
                    $bestChromosome = $trial;
                }
            }
            
            // Try Swap Mutation (Within same class)
            $bKelasId = $ctx['demands'][$blocks[$bIdx]['demand_idx']]['kelas_id'];
            $sameSizeBlocks = array_keys(array_filter($blocks, function($x) use ($size, $bKelasId, $ctx) {
                return $x['size'] === $size && $ctx['demands'][$x['demand_idx']]['kelas_id'] === $bKelasId;
            }));
            shuffle($sameSizeBlocks);
            $sampledBlocks = array_slice($sameSizeBlocks, 0, 20);
            foreach ($sampledBlocks as $otherBIdx) {
                if ($bIdx === $otherBIdx) continue;
                $trial = $bestChromosome;
                $temp = $trial['slots'][$bIdx];
                $trial['slots'][$bIdx] = $trial['slots'][$otherBIdx];
                $trial['slots'][$otherBIdx] = $temp;
                $trialScore = $this->evaluate($trial, $ctx)['total'];
                if ($trialScore < $bestScore) {
                    $bestScore = $trialScore;
                    $bestChromosome = $trial;
                }
            }
        }
        
        
        // Random swaps (Within same class)
        for ($i = 0; $i < 50; $i++) {
            $trial = $bestChromosome;
            $bIdx1 = array_rand($blocks);
            $bKelasId = $ctx['demands'][$blocks[$bIdx1]['demand_idx']]['kelas_id'];
            $sameClassBlocks = array_keys(array_filter($blocks, function($x) use ($bKelasId, $ctx) {
                return $ctx['demands'][$x['demand_idx']]['kelas_id'] === $bKelasId;
            }));
            if (count($sameClassBlocks) < 2) continue;
            
            $bIdx2 = $sameClassBlocks[array_rand($sameClassBlocks)];
            if ($blocks[$bIdx1]['size'] === $blocks[$bIdx2]['size']) {
                $temp = $trial['slots'][$bIdx1];
                $trial['slots'][$bIdx1] = $trial['slots'][$bIdx2];
                $trial['slots'][$bIdx2] = $temp;
                $trialScore = $this->evaluate($trial, $ctx)['total'];
                if ($trialScore < $bestScore) {
                    $bestScore = $trialScore;
                    $bestChromosome = $trial;
                }
            }
        }
        
        return $bestChromosome;
    }

    private function randFloat(): float
    {
        return mt_rand() / mt_getrandmax();
    }
}