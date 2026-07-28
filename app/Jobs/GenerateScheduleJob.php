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

    public $timeout = 1800;

    // GA parameters
    private int $populationSize = 200;
    private int $maxGenerations = 500;
    private float $crossoverRate = 0.8;
    private float $mutationRate = 0.1;
    private int $eliteCount = 2;
    private int $scheduleGenerationId;

    public function __construct(int $scheduleGenerationId)
    {
        $this->scheduleGenerationId = $scheduleGenerationId;
    }

    public function handle(): void
    {
        $genState = ScheduleGeneration::find($this->scheduleGenerationId);
        if (!$genState) return;
        
        $this->maxGenerations = $genState->max_generations ?? 500;
        
        $genState->update(['status' => 'running', 'message' => 'Memulai inisialisasi data...']);

        // ── LOAD DATA ──
        $jamList = JamPelajaran::all();
        $allDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $dbDays = $jamList->pluck('hari')->unique()->toArray();
        $hariAktif = array_values(array_intersect($allDays, $dbDays));

        $slotMap = [];
        $s = 0;
        foreach ($hariAktif as $hIdx => $hari) {
            $jamsForHari = $jamList->where('hari', trim($hari))->sortBy('jam_mulai');
            $jIdx = 0;
            foreach ($jamsForHari as $jam) {
                if ($jam->is_istirahat || $jam->jam_ke == 0) continue;
                
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

        // ── INITIALIZE GURU ASSIGNMENTS ──
        $assignedGurus = [];
        $guruLoad = [];
        
        $dIndices = array_keys($demands);
        // Sort demands to assign the ones with fewest eligible teachers first (MRV)
        usort($dIndices, function($a, $b) use ($demands) {
            $countA = array_sum(array_map('count', $demands[$a]['eligible_gurus']));
            $countB = array_sum(array_map('count', $demands[$b]['eligible_gurus']));
            return $countA <=> $countB;
        });

        foreach ($dIndices as $dIdx) {
            $demand = $demands[$dIdx];
            $picked = [];
            foreach ($demand['eligible_gurus'] as $mId => $eligible) {
                // Find eligible teacher with the minimum current load
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
            $assignedGurus[$dIdx] = $picked;
        }

        $evalContext = [
            'demands' => $demands,
            'blocks' => $blocks,
            'demandBlocks' => $demandBlocks,
            'slotMap' => $slotMap,
            'validBlockStarts' => $validBlockStarts,
            'gurus' => $assignedGurus,
        ];

        // ── INITIAL POPULATION ──
        $population = [];
        
        $smartCount = (int)($this->populationSize * 0.5);
        for ($i = 0; $i < $smartCount; $i++) {
            $population[] = $this->createSmartChromosome($evalContext);
        }
        for ($i = $smartCount; $i < $this->populationSize; $i++) {
            $population[] = $this->createRandomChromosome($evalContext);
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

            // Local search on the best chromosome of the generation (every 3 generations to stay fast)
            if ($gen % 3 === 0) {
                $repaired = $this->applyLocalSearch($indexed[0]['c'], $evalContext);
                $repairedEval = $this->evaluate($repaired, $evalContext);
                $repairedScore = $repairedEval['total'];
                $repairedFitness = 1.0 / (1.0 + $repairedScore);
                
                $indexed[0]['c'] = $repaired;
                $indexed[0]['s'] = $repairedScore;
                $indexed[0]['f'] = $repairedFitness;
                $population[$indexed[0]['idx']] = $repaired;
                $fitnessValues[$indexed[0]['idx']] = $repairedFitness;
                
                if ($repairedScore < $bestScore) {
                    $bestScore = $repairedScore;
                    $bestChromosome = $repaired;
                }
            }

            if ($bestScore < $lastBestScore) {
                $lastBestScore = $bestScore;
                $stagnantGenerations = 0;
            } else {
                $stagnantGenerations++;
            }
            
            if ($stagnantGenerations > 80) {
                \Illuminate\Support\Facades\Log::info("GA stagnant for 80 generations. Performing cataclysmic reset!");
                $newPop = [$bestChromosome];
                $smartCount = (int)($this->populationSize * 0.5);
                for ($i = 1; $i < $smartCount; $i++) {
                    $newPop[] = $this->createSmartChromosome($evalContext);
                }
                for ($i = $smartCount; $i < $this->populationSize; $i++) {
                    $newPop[] = $this->createRandomChromosome($evalContext);
                }
                $population = $newPop;
                $stagnantGenerations = 0;
                continue;
            }
            
            $currentMutationRate = $this->mutationRate;
            if ($stagnantGenerations > 40) {
                $currentMutationRate = 0.45;
            } elseif ($stagnantGenerations > 15) {
                $currentMutationRate = 0.25;
            } elseif ($stagnantGenerations > 5) {
                $currentMutationRate = 0.15;
            }

            if ($gen === 0) {
                \Illuminate\Support\Facades\Log::info("BEST CHROM EVAL (GEN 0): " . json_encode($this->evaluate($indexed[0]['c'], $evalContext)));
            }

            if ($gen % 10 === 0) {
                // Cek status pembatalan
                $currentCheck = ScheduleGeneration::find($this->scheduleGenerationId);
                if ($currentCheck && $currentCheck->status === 'cancelled') {
                    \Illuminate\Support\Facades\Log::info("Job generation cancelled by user.");
                    return;
                }

                $bestEval = $this->evaluate($bestChromosome, $evalContext);
                $hard = $bestEval['guru_conflicts'] + $bestEval['kelas_conflicts']; // same_day_mapel moved to dist_violations
                
                try {
                    $clashDetails = $this->getClashDetails($bestChromosome, $evalContext);
                    file_put_contents(storage_path('latest_clashes.txt'), implode("\n", $clashDetails));
                    file_put_contents(storage_path('best_chromosome.json'), json_encode($bestChromosome, JSON_PRETTY_PRINT));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to write latest clashes/chromosome: " . $e->getMessage());
                }

                $genState->update([
                    'generation' => $gen + 1,
                    'fitness' => $indexed[0]['f'],
                    'violations' => $hard,
                    'dist_violations' => $bestEval['dist_violations'] + $bestEval['same_day_mapel'],
                    'message' => "Evolusi generasi " . ($gen + 1) . "... (Hard: {$hard}, Packing: {$bestEval['packing_penalty']})",
                ]);
            }

            if ($bestScore === 0) break;

            $newPop = [];
            for ($i = 0; $i < $this->eliteCount; $i++) {
                $newPop[] = $indexed[$i]['c'];
            }

            if ($stagnantGenerations > 5 && count($indexed) > 0) {
                $best = $indexed[0]['c'];
                for ($j = 0; $j < 15; $j++) {
                    $newPop[] = $this->mutateRandom($best, $evalContext, 0.02);
                }
            }

            while (count($newPop) < $this->populationSize) {
                $p1 = $this->tournamentSelect($population, $fitnessValues);
                $p2 = $this->tournamentSelect($population, $fitnessValues);
                
                [$c1, $c2] = $this->crossoverOnePoint($p1, $p2);
                
                $c1 = $this->mutateRandom($c1, $evalContext, $currentMutationRate);
                $c2 = $this->mutateRandom($c2, $evalContext, $currentMutationRate);
                
                $newPop[] = $c1;
                if (count($newPop) < $this->populationSize) {
                    $newPop[] = $c2;
                }
            }
            $population = $newPop;
        }



        // ── SAVE RESULT ──
        $activeTahunAjaran = \App\Models\Pengaturan::activeTahunAjaran();
        Jadwal::where('tahun_ajaran', $activeTahunAjaran)->delete();

        if ($bestChromosome) {
            $entries = [];
            $savedKelasSlots = [];
            $savedGuruSlots = [];
            
            foreach ($bestChromosome['slots'] as $bIdx => $startSlot) {
                $block = $evalContext['blocks'][$bIdx];
                $dIdx = $block['demand_idx'];
                $demand = $evalContext['demands'][$dIdx];
                $guruMap = $bestChromosome['teachers'][$dIdx];
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
                                'tahun_ajaran' => $activeTahunAjaran,
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
            $hard = $final['guru_conflicts'] + $final['kelas_conflicts'];
            $finalFitness = round(1.0 / (1.0 + $final['total']), 6);
            $genState->update([
                'status' => 'done',
                'fitness' => $finalFitness,
                'violations' => $hard,
                'dist_violations' => $final['dist_violations'] + $final['same_day_mapel'],
                'message' => $hard > 0 ? "Jadwal digenerate dengan {$hard} bentrok. Perlu di-generate ulang." : "Jadwal berhasil digenerate tanpa bentrok.",
            ]);
        } else {
            $genState->update(['status' => 'failed', 'message' => 'Gagal menghasilkan jadwal']);
        }
    }

    private function createRandomChromosome(array $ctx): array
    {
        $blocks = $ctx['blocks'];
        $validBlockStarts = $ctx['validBlockStarts'];
        
        $slots = [];

        foreach ($blocks as $bIdx => $block) {
            $size = $block['size'];
            $validStarts = $validBlockStarts[$size];
            $slots[$bIdx] = $validStarts[array_rand($validStarts)];
        }

        $teachers = [];
        foreach ($ctx['demands'] as $dIdx => $demand) {
            $picked = [];
            foreach ($demand['eligible_gurus'] as $mId => $eligible) {
                $picked[$mId] = $eligible[array_rand($eligible)];
            }
            $teachers[$dIdx] = $picked;
        }

        return ['slots' => $slots, 'teachers' => $teachers];
    }

    private function createSmartChromosome(array $ctx): array
    {
        $demands = $ctx['demands'];
        $blocks = $ctx['blocks'];
        $validBlockStarts = $ctx['validBlockStarts'];
        $slotMap = $ctx['slotMap'];
        
        $slots = [];
        
        // 1. Assign Teachers (Load Balanced & Randomized Order)
        $gurus = [];
        $guruLoad = [];
        $dIndices = array_keys($demands);
        shuffle($dIndices);
        
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

        return ['slots' => $slots, 'teachers' => $gurus];
    }

    private function evaluate(array $chromosome, array $ctx): array
    {
        $demands = $ctx['demands'];
        $blocks = $ctx['blocks'];
        $slotMap = $ctx['slotMap'];
        
        $teacherTotalLoad = [];
        foreach ($chromosome['teachers'] as $dIdx => $gMap) {
            $demand = $demands[$dIdx];
            $hours = $demand['jam_per_minggu'];
            foreach ($gMap as $guruId) {
                $teacherTotalLoad[$guruId] = ($teacherTotalLoad[$guruId] ?? 0) + $hours;
            }
        }
        $overloadPenalty = 0;
        foreach ($teacherTotalLoad as $gId => $load) {
            if ($load > 24) {
                $overloadPenalty += ($load - 24) * 100;
            }
        }

        $guruSlots = [];
        $kelasSlots = [];
        $guruConflicts = 0;
        $kelasConflicts = 0;
        $conflictingBlocks = [];
        
        $guruDailyLoad = [];
        $kelasDailyOccupied = [];
        $frontLoadPenalty = 0;
        $kelasMapelDay = [];
        $sameDayMapelPenalty = 0;
        
        $maxSIdx = [];
        $totalSlotsUsed = [];

        foreach ($blocks as $bIdx => $block) {
            $dIdx = $block['demand_idx'];
            $demand = $demands[$dIdx];
            $guruMap = $chromosome['teachers'][$dIdx];
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
            }

            if ($hasConflict) {
                $conflictingBlocks[$bIdx] = true;
            }
        }


        foreach ($blocks as $bIdx => $block) {
            $dIdx = $block['demand_idx'];
            $demand = $demands[$dIdx];
            $guruMap = $chromosome['teachers'][$dIdx];
            $kelasId = $demand['kelas_id'];
            $start = $chromosome['slots'][$bIdx];
            $size = $block['size'];

            for ($i = 0; $i < $size; $i++) {
                $sIdx = $start + $i;
                $dayIdx = $slotMap[$sIdx]['hari_idx'];
                $kelasDailyOccupied[$kelasId][$dayIdx][$slotMap[$sIdx]['jam_pos']] = true;
                $frontLoadPenalty += $dayIdx;
                
                if (!isset($maxSIdx[$kelasId]) || $sIdx > $maxSIdx[$kelasId]) {
                    $maxSIdx[$kelasId] = $sIdx;
                }
                $totalSlotsUsed[$kelasId] = ($totalSlotsUsed[$kelasId] ?? 0) + 1;
            }

            $dayIdx = $slotMap[$start]['hari_idx'];
            foreach ($guruMap as $guruId) {
                $guruDailyLoad[$guruId][$dayIdx] = ($guruDailyLoad[$guruId][$dayIdx] ?? 0) + $size;
            }
            
            foreach ($demand['mapel_ids'] as $mapelId) {
                if (isset($kelasMapelDay[$kelasId][$dayIdx][$mapelId])) {
                    $sameDayMapelPenalty++;
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
        foreach ($kelasDailyOccupied as $kId => $days) {
            foreach ($days as $dIdx => $occupied) {
                $first = 999;
                $last = -1;
                $count = 0;
                for ($j = 0; $j < 11; $j++) {
                    if (isset($occupied[$j])) {
                        if ($first === 999) $first = $j;
                        $last = $j;
                        $count++;
                    }
                }
                if ($count > 0) {
                    $gaps = ($last - $first + 1) - $count;
                    $gapPenalties += ($gaps * 3) + $first;
                }
            }
        }

        $total = ($guruConflicts + $kelasConflicts) * 10000
               + $overloadPenalty
               + ($sameDayMapelPenalty * 10)
               + ($distViolations * 1) 
               + ($gapPenalties * 0.1) 
               + ($packingPenalty * 0.01)
               + ($frontLoadPenalty * 0.001);

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
        $best = null;
        $bestFitness = -1.0;
        for ($i = 0; $i < 3; $i++) {
            $idx = array_rand($population);
            if ($fitnessValues[$idx] > $bestFitness) {
                $bestFitness = $fitnessValues[$idx];
                $best = $population[$idx];
            }
        }
        return $best;
    }

    private function crossoverOnePoint(array $p1, array $p2): array
    {
        if ($this->randFloat() > $this->crossoverRate) {
            return [$p1, $p2];
        }

        $c1 = ['slots' => [], 'teachers' => []];
        $c2 = ['slots' => [], 'teachers' => []];
        
        $c1['slots'] = $p1['slots'];
        $c2['slots'] = $p2['slots'];

        // Crossover teacher assignments
        $demandKeys = array_keys($p1['teachers']);
        $cPointT = rand(1, count($demandKeys) - 1);
        foreach ($demandKeys as $i => $dIdx) {
            if ($i < $cPointT) {
                $c1['teachers'][$dIdx] = $p1['teachers'][$dIdx];
                $c2['teachers'][$dIdx] = $p2['teachers'][$dIdx];
            } else {
                $c1['teachers'][$dIdx] = $p2['teachers'][$dIdx];
                $c2['teachers'][$dIdx] = $p1['teachers'][$dIdx];
            }
        }

        return [$c1, $c2];
    }

    private function mutateRandom(array $chromosome, array $ctx, float $mutRate): array
    {
        $demands = $ctx['demands'];
        $blocks = $ctx['blocks'];
        $validBlockStarts = $ctx['validBlockStarts'];

        // Mutate slots randomly
        foreach ($blocks as $bIdx => $block) {
            if ($this->randFloat() < $mutRate) {
                $size = $block['size'];
                $validStarts = $validBlockStarts[$size];
                if (!empty($validStarts)) {
                    $chromosome['slots'][$bIdx] = $validStarts[array_rand($validStarts)];
                }
            }
        }

        // Mutate teachers randomly
        foreach ($demands as $dIdx => $demand) {
            if ($this->randFloat() < $mutRate) {
                foreach ($demand['eligible_gurus'] as $mId => $eligible) {
                    if (count($eligible) > 1) {
                        $chromosome['teachers'][$dIdx][$mId] = $eligible[array_rand($eligible)];
                    }
                }
            }
        }

        return $chromosome;
    }

    private function applyLocalSearch(array $chromosome, array $ctx): array
    {
        $demands = $ctx['demands'];
        $blocks = $ctx['blocks'];
        $validBlockStarts = $ctx['validBlockStarts'];

        // Targeted Joint Slot-Teacher Local Search (up to 3 passes)
        $improved = true;
        $pass = 0;
        while ($improved && $pass < 1) {
            $improved = false;
            $eval = $this->evaluate($chromosome, $ctx);
            $conflicts = $eval['conflicting_blocks'];
            
            if (!empty($conflicts)) {
                shuffle($conflicts);
                $toRepair = array_slice($conflicts, 0, mt_rand(1, 2));
                
                foreach ($toRepair as $bIdx) {
                    $block = $blocks[$bIdx];
                    $size = $block['size'];
                    $validStarts = $validBlockStarts[$size];
                    if (empty($validStarts)) continue;
                    
                    $dIdx = $block['demand_idx'];
                    $demand = $demands[$dIdx];
                    $kelasId = $demand['kelas_id'];
                    
                    $bestSlot = $chromosome['slots'][$bIdx];
                    $bestScore = $eval['total'];
                    $bestTeachers = $chromosome['teachers'][$dIdx];
                    
                    // 1. Try joint slot-teacher moves
                    shuffle($validStarts);
                    $teacherCombos = [[]];
                    foreach ($demand['eligible_gurus'] as $mId => $eligible) {
                        $nextCombos = [];
                        foreach ($teacherCombos as $combo) {
                            foreach ($eligible as $guruId) {
                                $nextCombos[] = $combo + [$mId => $guruId];
                            }
                        }
                        $teacherCombos = $nextCombos;
                    }
                    
                    foreach ($validStarts as $testSlot) {
                        $chromosome['slots'][$bIdx] = $testSlot;
                        foreach ($teacherCombos as $combo) {
                            $chromosome['teachers'][$dIdx] = $combo;
                            $testEval = $this->evaluate($chromosome, $ctx);
                            if ($testEval['total'] <= $bestScore) {
                                $bestScore = $testEval['total'];
                                $bestSlot = $testSlot;
                                $bestTeachers = $combo;
                                $eval = $testEval;
                                $improved = true;
                            }
                        }
                    }
                    $chromosome['slots'][$bIdx] = $bestSlot;
                    $chromosome['teachers'][$dIdx] = $bestTeachers;
                    
                    // 2. Try class-intra-swap moves
                    $sameClassBlocks = [];
                    foreach ($blocks as $idx => $b) {
                        if ($idx !== $bIdx && $b['size'] === $size && $demands[$b['demand_idx']]['kelas_id'] === $kelasId) {
                            $sameClassBlocks[] = $idx;
                        }
                    }
                    
                    if (!empty($sameClassBlocks)) {
                        shuffle($sameClassBlocks);
                        foreach ($sameClassBlocks as $swapTarget) {
                            $temp = $chromosome['slots'][$bIdx];
                            $chromosome['slots'][$bIdx] = $chromosome['slots'][$swapTarget];
                            $chromosome['slots'][$swapTarget] = $temp;
                            
                            $testEval = $this->evaluate($chromosome, $ctx);
                            if ($testEval['total'] <= $bestScore) {
                                $bestScore = $testEval['total'];
                                $bestSlot = $chromosome['slots'][$bIdx];
                                $eval = $testEval;
                                $improved = true;
                            } else {
                                // Revert
                                $temp = $chromosome['slots'][$bIdx];
                                $chromosome['slots'][$bIdx] = $chromosome['slots'][$swapTarget];
                                $chromosome['slots'][$swapTarget] = $temp;
                            }
                        }
                    }
                }
            }
            $pass++;
        }

        return $chromosome;
    }

    private function getClashDetails(array $chromosome, array $ctx): array
    {
        $demands = $ctx['demands'];
        $blocks = $ctx['blocks'];
        $slotMap = $ctx['slotMap'];
        
        $guruSlots = [];
        $kelasSlots = [];
        $details = [];
        
        foreach ($blocks as $bIdx => $block) {
            $dIdx = $block['demand_idx'];
            $demand = $demands[$dIdx];
            $start = $chromosome['slots'][$bIdx];
            $size = $block['size'];
            $guruMap = $chromosome['teachers'][$dIdx];
            $kelasId = $demand['kelas_id'];
            
            $kelas = \App\Models\Kelas::find($kelasId);
            $kelasName = $kelas ? $kelas->nama : "Kelas {$kelasId}";
            
            for ($i = 0; $i < $size; $i++) {
                $sIdx = $start + $i;
                $slot = $slotMap[$sIdx] ?? null;
                $day = $slot ? $slot['hari'] : "Hari {$sIdx}";
                $jam = $slot ? $slot['jam_ke'] : "Jam {$sIdx}";
                
                foreach ($guruMap as $mId => $guruId) {
                    $guru = \App\Models\Guru::with('user')->find($guruId);
                    $guruName = $guru ? ($guru->user->nama_lengkap ?? $guru->nama) : "Guru {$guruId}";
                    $mapel = \App\Models\Mapel::find($mId);
                    $mapelName = $mapel ? $mapel->nama : "Mapel {$mId}";
                    
                    if (isset($guruSlots[$guruId][$sIdx])) {
                        $otherInfo = $guruSlots[$guruId][$sIdx];
                        $details[] = "🚨 GURU CLASH: {$guruName} mengajar '{$mapelName}' di '{$kelasName}' pada {$day} jam ke-{$jam}, tapi beliau sudah mengajar '{$otherInfo['mapel']}' di '{$otherInfo['kelas']}'!";
                    }
                    $guruSlots[$guruId][$sIdx] = ['kelas' => $kelasName, 'mapel' => $mapelName];
                }
                
                if (isset($kelasSlots[$kelasId][$sIdx])) {
                    $otherInfo = $kelasSlots[$kelasId][$sIdx];
                    $details[] = "🚨 KELAS CLASH: {$kelasName} dijadwalkan '{$mapelName}' pada {$day} jam ke-{$jam}, tapi kelas ini sudah terpakai di '{$otherInfo['mapel']}'!";
                }
                $kelasSlots[$kelasId][$sIdx] = ['mapel' => $mapelName];
            }
        }
        
        return $details;
    }

    private function randFloat(): float
    {
        return mt_rand() / mt_getrandmax();
    }
}