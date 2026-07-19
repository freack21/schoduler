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
        
        for ($i = 0; $i < $this->populationSize; $i++) {
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

            $newPop = [];
            for ($i = 0; $i < $this->eliteCount; $i++) {
                $newPop[] = $indexed[$i]['c'];
            }

            while (count($newPop) < $this->populationSize) {
                $p1 = $this->rouletteWheelSelect($population, $fitnessValues);
                $p2 = $this->rouletteWheelSelect($population, $fitnessValues);
                
                [$c1, $c2] = $this->crossoverOnePoint($p1, $p2);
                
                $c1 = $this->mutateRandom($c1, $evalContext);
                $c2 = $this->mutateRandom($c2, $evalContext);
                
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

        $total = $guruConflicts 
               + $kelasConflicts 
               + ($sameDayMapelPenalty * 0.1)
               + ($distViolations * 0.01) 
               + ($gapPenalties * 0.001) 
               + ($packingPenalty * 0.0001)
               + ($frontLoadPenalty * 0.00001);

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

    private function rouletteWheelSelect(array $population, array $fitnessValues): array
    {
        $totalFitness = array_sum($fitnessValues);
        $r = $this->randFloat() * $totalFitness;
        $cumulative = 0.0;
        
        foreach ($population as $idx => $chromosome) {
            $cumulative += $fitnessValues[$idx];
            if ($cumulative >= $r) {
                return $chromosome;
            }
        }
        
        return $population[count($population) - 1];
    }

    private function crossoverOnePoint(array $p1, array $p2): array
    {
        if ($this->randFloat() > $this->crossoverRate) {
            return [$p1, $p2];
        }

        $c1 = ['gurus' => [], 'slots' => []];
        $c2 = ['gurus' => [], 'slots' => []];
        
        $totalBlocks = count($p1['slots']);
        if ($totalBlocks < 2) return [$p1, $p2];
        
        $crossoverPoint = rand(1, $totalBlocks - 1);

        $bIdxKeys = array_keys($p1['slots']);
        
        foreach ($bIdxKeys as $i => $bIdx) {
            if ($i < $crossoverPoint) {
                $c1['slots'][$bIdx] = $p1['slots'][$bIdx];
                $c2['slots'][$bIdx] = $p2['slots'][$bIdx];
            } else {
                $c1['slots'][$bIdx] = $p2['slots'][$bIdx];
                $c2['slots'][$bIdx] = $p1['slots'][$bIdx];
            }
        }
        
        $dIdxKeys = array_keys($p1['gurus']);
        $demandCrossoverPoint = (int) (($crossoverPoint / $totalBlocks) * count($dIdxKeys));
        if ($demandCrossoverPoint === 0) $demandCrossoverPoint = 1;
        
        foreach ($dIdxKeys as $i => $dIdx) {
            if ($i < $demandCrossoverPoint) {
                $c1['gurus'][$dIdx] = $p1['gurus'][$dIdx];
                $c2['gurus'][$dIdx] = $p2['gurus'][$dIdx];
            } else {
                $c1['gurus'][$dIdx] = $p2['gurus'][$dIdx];
                $c2['gurus'][$dIdx] = $p1['gurus'][$dIdx];
            }
        }

        return [$c1, $c2];
    }

    private function mutateRandom(array $chromosome, array $ctx): array
    {
        $demands = $ctx['demands'];
        $blocks = $ctx['blocks'];
        $validBlockStarts = $ctx['validBlockStarts'];

        // Mutate slots
        foreach ($blocks as $bIdx => $block) {
            if ($this->randFloat() < $this->mutationRate) {
                $size = $block['size'];
                $validStarts = $validBlockStarts[$size];
                if (!empty($validStarts)) {
                    $chromosome['slots'][$bIdx] = $validStarts[array_rand($validStarts)];
                }
            }
        }

        // Mutate gurus
        foreach ($demands as $dIdx => $demand) {
            if ($this->randFloat() < $this->mutationRate) {
                $eligibleMap = $demand['eligible_gurus'];
                $mIdToMutate = array_rand($eligibleMap);
                $eligible = $eligibleMap[$mIdToMutate];
                if (count($eligible) > 1) {
                    $chromosome['gurus'][$dIdx][$mIdToMutate] = $eligible[array_rand($eligible)];
                }
            }
        }

        return $chromosome;
    }

    private function randFloat(): float
    {
        return mt_rand() / mt_getrandmax();
    }
}