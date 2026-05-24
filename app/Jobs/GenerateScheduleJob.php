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

    // GA Parameters (optimized)
    private int $populationSize = 200;
    private int $maxGenerations = 100;
    private float $crossoverRate = 0.9;
    private float $mutationRate = 0.05;
    private int $eliteCount = 5;
    private int $tournamentSize = 5;

    // Penalty weights (hard constraints 10x higher than soft)
    private int $guruConflictWeight = 100;
    private int $kelasConflictWeight = 100;
    private int $distributionWeight = 50;
    private int $consecutiveWeight = 30;
    // [DIPERBAIKI] Compactness weight dihapus agar persebaran mapel lebih natural ke banyak hari
    private int $dayPriorityWeight = 50;    // Hari awal HARUS full sebelum hari berikutnya

    private function configureParameters(int $totalGenes, int $totalSlots): void
    {
        $this->populationSize = min(120, max(60, (int) ceil($totalGenes * 0.5)));
        $this->maxGenerations = min(80, max(40, (int) ceil($totalGenes * 0.35)));
        $this->tournamentSize = min(5, max(3, (int) ceil(sqrt($this->populationSize))));
        $this->eliteCount = max(3, (int) ceil($this->populationSize * 0.05));

        if ($totalGenes > 200) {
            $this->crossoverRate = 0.85;
            $this->mutationRate = 0.04;
        }

        if ($totalGenes > 400) {
            $this->populationSize = min($this->populationSize, 100);
            $this->maxGenerations = min($this->maxGenerations, 60);
        }
    }

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

        // Build gene list
        $genes = [];
        $mapelMaxPerHari = [];
        $mapelJamPerMinggu = [];
        foreach ($guruMapels as $gm) {
            $mapelMaxPerHari[$gm->mapel_id] = $gm->mapel->max_jam_per_hari;
            $mapelJamPerMinggu[$gm->mapel_id] = $gm->mapel->jam_per_minggu;
            for ($j = 0; $j < $gm->mapel->jam_per_minggu; $j++) {
                $genes[] = [
                    'guru_mapel_id' => $gm->id,
                    'guru_id' => $gm->guru_id,
                    'kelas_id' => $gm->kelas_id,
                    'mapel_id' => $gm->mapel_id,
                ];
            }
        }

        $totalGenes = count($genes);

        // Only non-istirahat slots
        $jamAktif = $jamPelajaranList->where('is_istirahat', false);
        $jamIds = $jamAktif->pluck('id')->toArray();

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

        $this->configureParameters($totalGenes, $totalSlots);
        Cache::put('ga_max_generations', $this->maxGenerations, 600);

        // ── STRUCTURAL: compute allowed slots per kelas ──
        $kelasLessonCount = [];
        foreach ($genes as $gene) {
            $kelasLessonCount[$gene['kelas_id']] = ($kelasLessonCount[$gene['kelas_id']] ?? 0) + 1;
        }

        // [DIPERBAIKI] Bebaskan semua kelas agar bisa menggunakan seluruh slot,
        // Ini memberi kebebasan GA untuk menyelesaikan bentrok.
        $allSlots = range(0, $totalSlots - 1);
        $kelasAllowedSlots = [];
        foreach ($kelasLessonCount as $kelasId => $lessonCount) {
            $kelasAllowedSlots[$kelasId] = $allSlots;
        }

        $evalContext = [
            'genes' => $genes,
            'slotMap' => $slotMap,
            'mapelMaxPerHari' => $mapelMaxPerHari,
            'mapelJamPerMinggu' => $mapelJamPerMinggu,
            'kelasAllowedSlots' => $kelasAllowedSlots,
            'totalHari' => $totalHari,
            'slotsPerDay' => $slotsPerDay,
        ];

        // ── INITIAL POPULATION ──
        $population = [];
        $population[] = $this->repairChromosome($this->createGreedyChromosome($genes, $slotMap, $kelasAllowedSlots), $evalContext);

        $smartCount = min(20, max(5, (int) round($this->populationSize * 0.25)));
        for ($i = 1; $i < $this->populationSize; $i++) {
            if ($i <= $smartCount) {
                $population[] = $this->repairChromosome($this->createSmartChromosome($totalGenes, $genes, $slotMap, $kelasAllowedSlots), $evalContext);
            } else {
                $chromosome = [];
                for ($g = 0; $g < $totalGenes; $g++) {
                    $allowed = $kelasAllowedSlots[$genes[$g]['kelas_id']];
                    $chromosome[] = $allowed[array_rand($allowed)];
                }
                $population[] = $this->repairChromosome($chromosome, $evalContext);
            }
        }

        $bestChromosome = null;
        $bestScore = PHP_INT_MAX;

        // ── EVOLUTION LOOP ──
        for ($gen = 0; $gen < $this->maxGenerations; $gen++) {
            // Evaluate
            $scores = [];
            foreach ($population as $chromosome) {
                $scores[] = $this->evaluate($chromosome, $evalContext);
            }

            // Find best
            $bestIdx = 0;
            for ($i = 1; $i < count($scores); $i++) {
                if ($scores[$i]['total'] < $scores[$bestIdx]['total']) {
                    $bestIdx = $i;
                }
            }

            if ($scores[$bestIdx]['total'] < $bestScore) {
                $bestScore = $scores[$bestIdx]['total'];
                $bestChromosome = $population[$bestIdx];
            }

            // Update progress
            $fitness = 1.0 / (1.0 + $bestScore);
            $hardViolations = $scores[$bestIdx]['guru_conflicts'] + $scores[$bestIdx]['kelas_conflicts'];

            // Refresh progress every generation so terminal feedback does not appear stuck.
            Cache::put('ga_generation', $gen + 1, 600);
            Cache::put('ga_fitness', round($fitness, 6), 600);
            Cache::put('ga_violations', $hardViolations, 600);
            Cache::put('ga_dist_violations', $scores[$bestIdx]['dist_violations'], 600);

            // Debug log every 50 generations
            if ($gen % 50 === 0) {
                Log::info("GA Gen {$gen}: Score={$bestScore}, Guru={$scores[$bestIdx]['guru_conflicts']}, Kelas={$scores[$bestIdx]['kelas_conflicts']}, Dist={$scores[$bestIdx]['dist_violations']}, Cons={$scores[$bestIdx]['consecutive_violations']}, DayPri={$scores[$bestIdx]['day_priority_penalty']}");
            }

            // Early stop
            if ($bestScore === 0) {
                // Update final cache if early stop triggered
                Cache::put('ga_generation', $gen + 1, 600);
                Cache::put('ga_fitness', 1.0, 600);
                break;
            }

            // Local search every 40 generations on the current best chromosome.
            if ($gen > 0 && $gen % 40 === 0 && $bestChromosome) {
                $improved = $this->localSearch($bestChromosome, $evalContext);
                $improvedScore = $this->evaluate($improved, $evalContext)['total'];
                if ($improvedScore < $bestScore) {
                    $bestScore = $improvedScore;
                    $bestChromosome = $improved;
                    // Inject back into population
                    $population[0] = $bestChromosome;
                }
            }

            // Elitism
            $indexed = [];
            for ($i = 0; $i < count($population); $i++) {
                $indexed[] = ['c' => $population[$i], 's' => $scores[$i]['total']];
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
                    [$c1, $c2] = $this->uniformCrossover($p1, $p2);
                } else {
                    $c1 = $p1;
                    $c2 = $p2;
                }

                $c1 = $this->smartMutate($c1, $evalContext, $totalSlots);
                $c2 = $this->smartMutate($c2, $evalContext, $totalSlots);
                $c1 = $this->repairChromosome($c1, $evalContext);
                $c2 = $this->repairChromosome($c2, $evalContext);

                $newPop[] = $c1;
                if (count($newPop) < $this->populationSize) {
                    $newPop[] = $c2;
                }
            }

            $population = $newPop;
        }

        // ── SAVE RESULT ──
        Jadwal::truncate();

        if ($bestChromosome) {
            $entries = [];
            for ($g = 0; $g < $totalGenes; $g++) {
                $slot = $slotMap[$bestChromosome[$g]];
                $entries[] = [
                    'guru_mapel_id' => $genes[$g]['guru_mapel_id'],
                    'hari' => $slot['hari'],
                    'jam_pelajaran_id' => $slot['jam_pelajaran_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
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

        Log::info("GA DONE: Score={$final['total']}, Hard={$hard}, Dist={$final['dist_violations']}, Cons={$final['consecutive_violations']}, DayPri={$final['day_priority_penalty']}");

        if ($hard === 0 && $final['dist_violations'] === 0 && $final['consecutive_violations'] === 0) {
            Cache::put('ga_message', 'Jadwal berhasil digenerate tanpa bentrok, persebaran & urutan optimal! 🎯', 600);
        } elseif ($hard === 0) {
            Cache::put('ga_message', "Jadwal tanpa bentrok! Soft: {$final['dist_violations']} distribusi, {$final['consecutive_violations']} urutan.", 600);
        } else {
            Cache::put('ga_message', "Jadwal digenerate dengan {$hard} bentrok. Perlu di-generate ulang.", 600);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    //  SMART INITIALIZATION
    // ═══════════════════════════════════════════════════════════════

    private function createSmartChromosome(int $totalGenes, array $genes, array $slotMap, array $kelasAllowedSlots): array
    {
        $chromosome = array_fill(0, $totalGenes, 0);
        $usedGuruSlots = [];
        $usedKelasSlots = [];

        $kelasMapelPlacements = [];

        $indices = range(0, $totalGenes - 1);
        shuffle($indices);

        foreach ($indices as $g) {
            $gene = $genes[$g];
            $bestSlot = -1;
            $bestScore = -PHP_INT_MAX;
            $allowed = $kelasAllowedSlots[$gene['kelas_id']];
            $key = "{$gene['kelas_id']}-{$gene['mapel_id']}";

            foreach ($allowed as $s) {
                $slot = $slotMap[$s];

                if (isset($usedGuruSlots[$gene['guru_id']][$s])) continue;
                if (isset($usedKelasSlots[$gene['kelas_id']][$s])) continue;

                $score = 0;

                // Prefer earlier slots
                $score += (count($allowed) - $s) * 0.1;

                // Big bonus for consecutive with same mapel on same day
                if (isset($kelasMapelPlacements[$key])) {
                    foreach ($kelasMapelPlacements[$key] as $hariIdx => $positions) {
                        if ($hariIdx === $slot['hari_idx']) {
                            foreach ($positions as $pos) {
                                if (abs($slot['jam_pos'] - $pos) === 1) {
                                    $score += 100;
                                } elseif (abs($slot['jam_pos'] - $pos) === 2) {
                                    $score += 20;
                                }
                            }
                        }
                    }
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestSlot = $s;
                }
            }

            if ($bestSlot === -1) {
                $bestSlot = $allowed[array_rand($allowed)];
            }

            $chromosome[$g] = $bestSlot;
            $usedGuruSlots[$gene['guru_id']][$bestSlot] = true;
            $usedKelasSlots[$gene['kelas_id']][$bestSlot] = true;
            $kelasMapelPlacements[$key][$slotMap[$bestSlot]['hari_idx']][] = $slotMap[$bestSlot]['jam_pos'];
        }

        return $chromosome;
    }

    private function createGreedyChromosome(array $genes, array $slotMap, array $kelasAllowedSlots): array
    {
        $geneCount = count($genes);
        $chromosome = array_fill(0, $geneCount, 0);
        $guruSlots = [];
        $kelasSlots = [];
        $kelasDayCount = [];
        $teacherLoad = [];
        $kelasLoad = [];

        foreach ($genes as $gene) {
            $teacherLoad[$gene['guru_id']] = ($teacherLoad[$gene['guru_id']] ?? 0) + 1;
            $kelasLoad[$gene['kelas_id']] = ($kelasLoad[$gene['kelas_id']] ?? 0) + 1;
        }

        $indices = range(0, $geneCount - 1);
        usort($indices, function ($a, $b) use ($genes, $teacherLoad, $kelasLoad) {
            $scoreA = ($teacherLoad[$genes[$a]['guru_id']] ?? 0) + ($kelasLoad[$genes[$a]['kelas_id']] ?? 0) * 1.2;
            $scoreB = ($teacherLoad[$genes[$b]['guru_id']] ?? 0) + ($kelasLoad[$genes[$b]['kelas_id']] ?? 0) * 1.2;
            return $scoreB <=> $scoreA;
        });

        foreach ($indices as $g) {
            $gene = $genes[$g];
            $bestSlot = null;
            $bestScore = PHP_INT_MAX;
            $allowedSlots = $kelasAllowedSlots[$gene['kelas_id']];

            foreach ($allowedSlots as $slotIndex) {
                $conflicts = 0;
                if (isset($guruSlots[$gene['guru_id']][$slotIndex])) {
                    $conflicts += 1000;
                }
                if (isset($kelasSlots[$gene['kelas_id']][$slotIndex])) {
                    $conflicts += 1000;
                }

                $hariIdx = $slotMap[$slotIndex]['hari_idx'];
                $sameDayCount = $kelasDayCount[$gene['kelas_id']][$hariIdx] ?? 0;
                $jamPos = $slotMap[$slotIndex]['jam_pos'];
                $score = $conflicts + ($sameDayCount * 10) + ($hariIdx * 5) + $jamPos;

                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestSlot = $slotIndex;
                    if ($score === 0) {
                        break;
                    }
                }
            }

            if ($bestSlot === null) {
                $bestSlot = $allowedSlots[array_rand($allowedSlots)];
            }

            $chromosome[$g] = $bestSlot;
            $guruSlots[$gene['guru_id']][$bestSlot] = true;
            $kelasSlots[$gene['kelas_id']][$bestSlot] = true;
            $kelasDayCount[$gene['kelas_id']][$slotMap[$bestSlot]['hari_idx']] = ($kelasDayCount[$gene['kelas_id']][$slotMap[$bestSlot]['hari_idx']] ?? 0) + 1;
        }

        return $chromosome;
    }

    private function repairChromosome(array $chromosome, array $ctx): array
    {
        $genes = $ctx['genes'];
        $slotMap = $ctx['slotMap'];
        $kelasAllowedSlots = $ctx['kelasAllowedSlots'];

        $guruSlots = [];
        $kelasSlots = [];
        $conflictIndices = [];

        foreach ($chromosome as $g => $slotIdx) {
            $guruId = $genes[$g]['guru_id'];
            $kelasId = $genes[$g]['kelas_id'];

            if (isset($guruSlots[$guruId][$slotIdx]) || isset($kelasSlots[$kelasId][$slotIdx])) {
                $conflictIndices[] = $g;
                continue;
            }

            $guruSlots[$guruId][$slotIdx] = true;
            $kelasSlots[$kelasId][$slotIdx] = true;
        }

        foreach ($conflictIndices as $g) {
            $gene = $genes[$g];
            $allowed = $kelasAllowedSlots[$gene['kelas_id']];
            $bestSlot = null;
            $bestScore = PHP_INT_MAX;

            foreach ($allowed as $slotIdx) {
                $conflictCount = 0;
                if (isset($guruSlots[$gene['guru_id']][$slotIdx])) {
                    $conflictCount += 1000;
                }
                if (isset($kelasSlots[$gene['kelas_id']][$slotIdx])) {
                    $conflictCount += 1000;
                }

                $slot = $slotMap[$slotIdx];
                $score = $conflictCount + ($slot['hari_idx'] * 10) + $slot['jam_pos'];

                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestSlot = $slotIdx;
                    if ($score === 0) {
                        break;
                    }
                }
            }

            if ($bestSlot === null) {
                $bestSlot = $allowed[array_rand($allowed)];
            }

            $chromosome[$g] = $bestSlot;
            $guruSlots[$gene['guru_id']][$bestSlot] = true;
            $kelasSlots[$gene['kelas_id']][$bestSlot] = true;
        }

        return $chromosome;
    }

    private function biasedRandSlot(int $totalSlots): int
    {
        $r = $this->randFloat();
        return (int) floor($r * $r * $totalSlots) % $totalSlots;
    }

    // ═══════════════════════════════════════════════════════════════
    //  FITNESS EVALUATION
    // ═══════════════════════════════════════════════════════════════

    private function evaluate(array $chromosome, array $ctx): array
    {
        $genes = $ctx['genes'];
        $slotMap = $ctx['slotMap'];
        $mapelMaxPerHari = $ctx['mapelMaxPerHari'];
        $totalHari = $ctx['totalHari'];
        $slotsPerDay = $ctx['slotsPerDay'];

        $guruConflicts = 0;
        $kelasConflicts = 0;
        $distViolations = 0;
        $consecutiveViolations = 0;
        $dayPriorityPenalty = 0;

        $guruSlots = [];
        $kelasSlots = [];
        $kelasMapelHari = [];
        $kelasHariFill = [];

        for ($g = 0; $g < count($chromosome); $g++) {
            $slotIdx = $chromosome[$g];
            $guruId = $genes[$g]['guru_id'];
            $kelasId = $genes[$g]['kelas_id'];
            $mapelId = $genes[$g]['mapel_id'];
            $hariIdx = $slotMap[$slotIdx]['hari_idx'];
            $jamPos = $slotMap[$slotIdx]['jam_pos'];

            if (isset($guruSlots[$guruId][$slotIdx])) {
                $guruConflicts++;
            }
            $guruSlots[$guruId][$slotIdx] = true;

            if (isset($kelasSlots[$kelasId][$slotIdx])) {
                $kelasConflicts++;
            }
            $kelasSlots[$kelasId][$slotIdx] = true;

            $key = "{$kelasId}-{$mapelId}";
            $kelasMapelHari[$key][$hariIdx][] = $jamPos;

            $kelasHariFill[$kelasId][$hariIdx] = ($kelasHariFill[$kelasId][$hariIdx] ?? 0) + 1;
        }

        // Soft: distribution + consecutive (Compactness removed)
        foreach ($kelasMapelHari as $key => $hariData) {
            $mapelId = (int) explode('-', $key)[1];
            $maxPerHari = $mapelMaxPerHari[$mapelId] ?? 2;

            foreach ($hariData as $hariIdx => $positions) {
                $count = count($positions);

                // Distribution: too many on one day
                if ($count > $maxPerHari) {
                    $distViolations += ($count - $maxPerHari);
                }

                // Consecutive: positions should be adjacent
                if ($count > 1) {
                    sort($positions);
                    for ($i = 1; $i < $count; $i++) {
                        if ($positions[$i] !== $positions[$i - 1] + 1) {
                            $consecutiveViolations++;
                        }
                    }
                }
            }
        }

        // Soft: day priority — STRICT fill from left
        foreach ($kelasHariFill as $kelasId => $hariFills) {
            for ($d = 0; $d < $totalHari; $d++) {
                $fill = $hariFills[$d] ?? 0;
                $emptySlots = $slotsPerDay - $fill;

                if ($emptySlots > 0) {
                    $laterHasLessons = false;
                    for ($d2 = $d + 1; $d2 < $totalHari; $d2++) {
                        if (($hariFills[$d2] ?? 0) > 0) {
                            $laterHasLessons = true;
                            break;
                        }
                    }
                    if ($laterHasLessons) {
                        $dayPriorityPenalty += $emptySlots;
                    }
                }
            }
        }

        $total = ($guruConflicts * $this->guruConflictWeight)
            + ($kelasConflicts * $this->kelasConflictWeight)
            + ($distViolations * $this->distributionWeight)
            + ($consecutiveViolations * $this->consecutiveWeight)
            + ($dayPriorityPenalty * $this->dayPriorityWeight);

        return [
            'guru_conflicts' => $guruConflicts,
            'kelas_conflicts' => $kelasConflicts,
            'dist_violations' => $distViolations,
            'consecutive_violations' => $consecutiveViolations,
            'day_priority_penalty' => $dayPriorityPenalty,
            'total' => $total,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    //  SELECTION
    // ═══════════════════════════════════════════════════════════════

    private function tournamentSelect(array $pop, array $fit): array
    {
        $best = rand(0, count($pop) - 1);
        for ($i = 1; $i < $this->tournamentSize; $i++) {
            $idx = rand(0, count($pop) - 1);
            if ($fit[$idx] > $fit[$best]) {
                $best = $idx;
            }
        }
        return $pop[$best];
    }

    // ═══════════════════════════════════════════════════════════════
    //  CROSSOVER
    // ═══════════════════════════════════════════════════════════════

    private function uniformCrossover(array $p1, array $p2): array
    {
        $c1 = [];
        $c2 = [];
        for ($i = 0; $i < count($p1); $i++) {
            if ($this->randFloat() < 0.5) {
                $c1[] = $p1[$i];
                $c2[] = $p2[$i];
            } else {
                $c1[] = $p2[$i];
                $c2[] = $p1[$i];
            }
        }
        return [$c1, $c2];
    }

    // ═══════════════════════════════════════════════════════════════
    //  SMART MUTATION
    // ═══════════════════════════════════════════════════════════════

    private function smartMutate(array $chromosome, array $ctx, int $totalSlots): array
    {
        $genes = $ctx['genes'];
        $kelasAllowedSlots = $ctx['kelasAllowedSlots'];
        $geneCount = count($chromosome);

        $guruSlots = [];
        $kelasSlots = [];
        $conflictIndices = [];

        for ($g = 0; $g < $geneCount; $g++) {
            $slotIdx = $chromosome[$g];
            $guruId = $genes[$g]['guru_id'];
            $kelasId = $genes[$g]['kelas_id'];

            if (isset($guruSlots[$guruId][$slotIdx]) || isset($kelasSlots[$kelasId][$slotIdx])) {
                $conflictIndices[] = $g;
            }
            $guruSlots[$guruId][$slotIdx] = true;
            $kelasSlots[$kelasId][$slotIdx] = true;
        }

        if (!empty($conflictIndices)) {
            $mutCount = max(1, (int) (count($conflictIndices) * 0.4));
            shuffle($conflictIndices);

            for ($i = 0; $i < min($mutCount, count($conflictIndices)); $i++) {
                $idx = $conflictIndices[$i];
                $allowed = $kelasAllowedSlots[$genes[$idx]['kelas_id']];

                $blockedSlots = [];
                for ($g2 = 0; $g2 < $geneCount; $g2++) {
                    if ($g2 === $idx) continue;
                    $s2 = $chromosome[$g2];
                    if ($genes[$g2]['guru_id'] === $genes[$idx]['guru_id'] || $genes[$g2]['kelas_id'] === $genes[$idx]['kelas_id']) {
                        $blockedSlots[$s2] = true;
                    }
                }

                $available = [];
                foreach ($allowed as $s) {
                    if (!isset($blockedSlots[$s])) {
                        $available[] = $s;
                    }
                }

                if (!empty($available)) {
                    $picked = $available[0];
                    if (count($available) > 3) {
                        $topN = max(1, (int) (count($available) * 0.3));
                        $picked = $available[rand(0, $topN - 1)];
                    }
                    $chromosome[$idx] = $picked;
                }
            }
        } else {
            for ($g = 0; $g < $geneCount; $g++) {
                if ($this->randFloat() < $this->mutationRate) {
                    $allowed = $kelasAllowedSlots[$genes[$g]['kelas_id']];
                    $chromosome[$g] = $allowed[array_rand($allowed)];
                }
            }
        }

        return $chromosome;
    }

    // ═══════════════════════════════════════════════════════════════
    //  LOCAL SEARCH
    // ═══════════════════════════════════════════════════════════════

    private function localSearch(array $chromosome, array $ctx): array
    {
        $genes = $ctx['genes'];
        $kelasAllowedSlots = $ctx['kelasAllowedSlots'];
        $bestScore = $this->evaluate($chromosome, $ctx)['total'];
        $bestChromosome = $chromosome;

        // Strategy 1: Relocate conflicting genes
        $guruSlots = [];
        $kelasSlots = [];
        $conflictPairs = [];

        for ($g = 0; $g < count($chromosome); $g++) {
            $s = $chromosome[$g];
            $guruId = $genes[$g]['guru_id'];
            $kelasId = $genes[$g]['kelas_id'];

            if (isset($guruSlots[$guruId][$s])) {
                $conflictPairs[] = [$guruSlots[$guruId][$s], $g];
            }
            $guruSlots[$guruId][$s] = $g;

            if (isset($kelasSlots[$kelasId][$s])) {
                $conflictPairs[] = [$kelasSlots[$kelasId][$s], $g];
            }
            $kelasSlots[$kelasId][$s] = $g;
        }

        foreach ($conflictPairs as [$g1, $g2]) {
            $allowed = $kelasAllowedSlots[$genes[$g2]['kelas_id']];
            foreach ($allowed as $s) {
                if ($s === $chromosome[$g2]) continue;
                $trial = $bestChromosome;
                $trial[$g2] = $s;
                $trialScore = $this->evaluate($trial, $ctx)['total'];
                if ($trialScore < $bestScore) {
                    $bestScore = $trialScore;
                    $bestChromosome = $trial;
                }
            }
            if ($bestScore === 0) return $bestChromosome;
        }

        // [DIPERBAIKI] Strategy 2: Swap mapel yang BERBEDA dalam KELAS YANG SAMA 
        // untuk memperbaiki distribusi (day priority/persebaran).
        $genesByKelas = [];
        for ($g = 0; $g < count($genes); $g++) {
            $genesByKelas[$genes[$g]['kelas_id']][] = $g;
        }

        foreach ($genesByKelas as $indices) {
            if (count($indices) < 2) continue;

            // Lakukan 10 sampel percobaan swap acak per kelas untuk menghemat komputasi
            for ($iter = 0; $iter < 10; $iter++) {
                $idx1 = $indices[array_rand($indices)];
                $idx2 = $indices[array_rand($indices)];
                
                // Jangan diswap jika mapelnya sama (karena tidak akan mengubah apapun)
                if ($genes[$idx1]['mapel_id'] === $genes[$idx2]['mapel_id']) continue;

                $trial = $bestChromosome;
                $temp = $trial[$idx1];
                $trial[$idx1] = $trial[$idx2];
                $trial[$idx2] = $temp;

                $trialScore = $this->evaluate($trial, $ctx)['total'];
                if ($trialScore < $bestScore) {
                    $bestScore = $trialScore;
                    $bestChromosome = $trial;
                }
            }
            if ($bestScore === 0) return $bestChromosome;
        }

        return $bestChromosome;
    }

    // ═══════════════════════════════════════════════════════════════

    private function randFloat(): float
    {
        return mt_rand() / mt_getrandmax();
    }
}