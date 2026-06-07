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
        if (!$genState) return;
        
        $genState->update(['status' => 'running', 'message' => 'Memulai inisialisasi data...']);

        // ── LOAD DATA ──
        $jamList = JamPelajaran::orderBy('jam_ke')->get();
        $totalJamPerHari = $jamList->count();
        $hariAktif = explode(',', \App\Models\Pengaturan::where('key', 'hari_aktif')->value('value') ?? 'Senin,Selasa,Rabu,Kamis,Jumat');
        $totalSlots = count($hariAktif) * $totalJamPerHari;

        $slotMap = [];
        $s = 0;
        foreach ($hariAktif as $hIdx => $hari) {
            foreach ($jamList as $jIdx => $jam) {
                $slotMap[$s] = [
                    'hari' => trim($hari),
                    'hari_idx' => $hIdx,
                    'jam_ke' => $jam->jam_ke,
                    'jam_pos' => $jIdx,
                    'jam_pelajaran_id' => $jam->id,
                    'is_istirahat' => $jam->is_istirahat,
                ];
                $s++;
            }
        }

        // PRECOMPUTE VALID BLOCK STARTS
        $validBlockStarts = [];
        for ($size = 1; $size <= 4; $size++) {
            $validBlockStarts[$size] = [];
            for ($slot = 0; $slot <= $totalSlots - $size; $slot++) {
                $startHari = $slotMap[$slot]['hari_idx'];
                $endHari = $slotMap[$slot + $size - 1]['hari_idx'];
                if ($startHari === $endHari) {
                    $isConsecutive = true;
                    for ($j = 1; $j < $size; $j++) {
                        if ($slotMap[$slot + $j]['jam_pos'] !== $slotMap[$slot + $j - 1]['jam_pos'] + 1 || $slotMap[$slot + $j]['is_istirahat']) {
                            $isConsecutive = false;
                            break;
                        }
                    }
                    if ($isConsecutive) {
                        $validBlockStarts[$size][] = $slot;
                    }
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
        $guruMapelGrouped = GuruMapel::all()->groupBy('mapel_id');

        foreach ($kelasList as $k) {
            $kuriList = $kurikulumList->where('tingkat_id', $k->tingkat_id);
            if ($k->jurusan_id) {
                $kuriList = $kuriList->filter(function($kuri) use ($k) {
                    return is_null($kuri->jurusan_id) || $kuri->jurusan_id == $k->jurusan_id;
                });
            } else {
                $kuriList = $kuriList->whereNull('jurusan_id');
            }

            foreach ($kuriList as $kuri) {
                $eligibleGurus = isset($guruMapelGrouped[$kuri->mapel_id]) 
                    ? $guruMapelGrouped[$kuri->mapel_id]->pluck('guru_id')->toArray() 
                    : [];

                if (empty($eligibleGurus)) continue;

                $demands[] = [
                    'kelas_id' => $k->id,
                    'mapel_id' => $kuri->mapel_id,
                    'jam_per_minggu' => $kuri->mapel->jam_per_minggu,
                    'jam_per_hari' => $kuri->mapel->jam_per_hari,
                    'eligible_gurus' => array_values($eligibleGurus)
                ];
            }
        }

        // ── GENERATE BLOCKS ──
        $blocks = [];
        $demandBlocks = [];
        foreach ($demands as $dIdx => $demand) {
            $sisa = $demand['jam_per_minggu'];
            $maxPerHari = $demand['jam_per_hari'];
            
            while ($sisa > 0) {
                $take = min($sisa, $maxPerHari);
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
                
                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestChromosome = $chromosome;
                }
            }

            usort($indexed, fn($a, $b) => $b['f'] <=> $a['f']);

            if ($gen % 10 === 0) {
                $bestEval = $this->evaluate($bestChromosome, $evalContext);
                $hard = $bestEval['guru_conflicts'] + $bestEval['kelas_conflicts'];
                $genState->update([
                    'generation' => $gen + 1,
                    'fitness' => $indexed[0]['f'],
                    'violations' => $hard,
                    'dist_violations' => $bestEval['dist_violations'],
                    'message' => "Evolusi generasi " . ($gen + 1) . "... (Hard: {$hard})",
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
            while ($repairRounds < 50) {
                $eval = $this->evaluate($bestChromosome, $evalContext);
                $hardConflicts = $eval['guru_conflicts'] + $eval['kelas_conflicts'];
                if ($hardConflicts === 0) break;
                
                $conflictingBlocks = $eval['conflicting_blocks'];
                if (empty($conflictingBlocks)) break;
                
                $improved = false;
                foreach ($conflictingBlocks as $b) {
                    $blockSize = $evalContext['blocks'][$b]['size'];
                    $validStarts = $evalContext['validBlockStarts'][$blockSize];
                    $currentScore = $eval['total'];
                    
                    $bestNewSlot = $bestChromosome['slots'][$b];
                    $bestNewScore = $currentScore;
                    
                    // Shuffle starts for randomness in repair
                    shuffle($validStarts);
                    
                    foreach ($validStarts as $s) {
                        if ($s === $bestChromosome['slots'][$b]) continue;
                        $trial = $bestChromosome;
                        $trial['slots'][$b] = $s;
                        $trialEval = $this->evaluate($trial, $evalContext);
                        
                        if ($trialEval['total'] < $bestNewScore) {
                            $bestNewScore = $trialEval['total'];
                            $bestNewSlot = $s;
                            $improved = true;
                        }
                    }
                    
                    if ($bestNewSlot !== $bestChromosome['slots'][$b]) {
                        $bestChromosome['slots'][$b] = $bestNewSlot;
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
            foreach ($bestChromosome['slots'] as $bIdx => $startSlot) {
                $block = $evalContext['blocks'][$bIdx];
                $dIdx = $block['demand_idx'];
                $demand = $evalContext['demands'][$dIdx];
                $guruId = $bestChromosome['gurus'][$dIdx];
                
                for ($i = 0; $i < $block['size']; $i++) {
                    $slot = $slotMap[$startSlot + $i];
                    $entries[] = [
                        'guru_id' => $guruId,
                        'mapel_id' => $demand['mapel_id'],
                        'kelas_id' => $demand['kelas_id'],
                        'hari' => $slot['hari'],
                        'jam_pelajaran_id' => $slot['jam_pelajaran_id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
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
                'dist_violations' => $final['dist_violations'],
                'message' => $hard > 0 ? "Jadwal digenerate dengan {$hard} bentrok. Perlu di-generate ulang." : "Jadwal berhasil digenerate tanpa bentrok keras!",
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
            $gurus[$dIdx] = $demand['eligible_gurus'][array_rand($demand['eligible_gurus'])];
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
        usort($dIndices, fn($a, $b) => count($demands[$a]['eligible_gurus']) <=> count($demands[$b]['eligible_gurus']));

        foreach ($dIndices as $dIdx) {
            $demand = $demands[$dIdx];
            $eligible = $demand['eligible_gurus'];
            
            $bestGuru = $eligible[0];
            $minLoad = PHP_INT_MAX;
            foreach ($eligible as $gid) {
                $load = $guruLoad[$gid] ?? 0;
                if ($load < $minLoad) {
                    $minLoad = $load;
                    $bestGuru = $gid;
                }
            }
            $gurus[$dIdx] = $bestGuru;
            $guruLoad[$bestGuru] = ($guruLoad[$bestGuru] ?? 0) + $demand['jam_per_minggu'];
        }

        // 2. Assign Slots (Minimize Conflicts)
        $usedGuruSlots = [];
        $usedKelasSlots = [];
        
        $bIndices = array_keys($blocks);
        usort($bIndices, fn($a, $b) => $blocks[$b]['size'] <=> $blocks[$a]['size']);

        foreach ($bIndices as $bIdx) {
            $block = $blocks[$bIdx];
            $dIdx = $block['demand_idx'];
            $demand = $demands[$dIdx];
            $guruId = $gurus[$dIdx];
            $kelasId = $demand['kelas_id'];
            $size = $block['size'];
            
            $validStarts = $validBlockStarts[$size];
            shuffle($validStarts); // Add some randomness
            
            $bestSlot = $validStarts[0] ?? 0;
            $minConflicts = PHP_INT_MAX;
            
            foreach ($validStarts as $s) {
                $conflicts = 0;
                for ($i = 0; $i < $size; $i++) {
                    $sIdx = $s + $i;
                    if (isset($usedGuruSlots[$guruId][$sIdx])) $conflicts++;
                    if (isset($usedKelasSlots[$kelasId][$sIdx])) $conflicts++;
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
            for ($i = 0; $i < $size; $i++) {
                $usedGuruSlots[$guruId][$bestSlot + $i] = true;
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

        foreach ($blocks as $bIdx => $block) {
            $dIdx = $block['demand_idx'];
            $demand = $demands[$dIdx];
            $guruId = $chromosome['gurus'][$dIdx];
            $kelasId = $demand['kelas_id'];
            $start = $chromosome['slots'][$bIdx];
            $size = $block['size'];
            
            $hasConflict = false;
            for ($i = 0; $i < $size; $i++) {
                $sIdx = $start + $i;
                if (isset($guruSlots[$guruId][$sIdx])) {
                    $guruConflicts++;
                    $hasConflict = true;
                }
                if (isset($kelasSlots[$kelasId][$sIdx])) {
                    $kelasConflicts++;
                    $hasConflict = true;
                }
                $guruSlots[$guruId][$sIdx] = true;
                $kelasSlots[$kelasId][$sIdx] = true;
            }
            if ($hasConflict) {
                $conflictingBlocks[$bIdx] = true;
            }

            $dayIdx = $slotMap[$start]['hari_idx'];
            $guruDailyLoad[$guruId][$dayIdx] = ($guruDailyLoad[$guruId][$dayIdx] ?? 0) + $size;
        }

        $distViolations = 0;
        foreach ($guruDailyLoad as $gid => $days) {
            foreach ($days as $load) {
                if ($load > 6) {
                    $distViolations += ($load - 6);
                }
            }
        }

        $total = ($guruConflicts * 100) + ($kelasConflicts * 100) + ($distViolations * 10);

        return [
            'guru_conflicts' => $guruConflicts,
            'kelas_conflicts' => $kelasConflicts,
            'dist_violations' => $distViolations,
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
                $eligible = $demands[$dIdx]['eligible_gurus'];
                if (count($eligible) > 1) {
                    $chromosome['gurus'][$dIdx] = $eligible[array_rand($eligible)];
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
        }
        
        for ($i = 0; $i < 100; $i++) {
            $trial = $bestChromosome;
            $bIdx = array_rand($blocks);
            $size = $blocks[$bIdx]['size'];
            $validStarts = $validBlockStarts[$size];
            $trial['slots'][$bIdx] = $validStarts[array_rand($validStarts)];
            
            $trialScore = $this->evaluate($trial, $ctx)['total'];
            if ($trialScore < $bestScore) {
                $bestScore = $trialScore;
                $bestChromosome = $trial;
            }
        }
        
        return $bestChromosome;
    }

    private function randFloat(): float
    {
        return mt_rand() / mt_getrandmax();
    }
}