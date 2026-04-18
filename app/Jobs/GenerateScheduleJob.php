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
    private int $maxGenerations = 2000;
    private float $crossoverRate = 0.9;
    private float $mutationRate = 0.05;
    private int $eliteCount = 5;
    private int $tournamentSize = 5;

    // Penalty weights (hard constraints 10x higher than soft)
    private int $guruConflictWeight = 100;
    private int $kelasConflictWeight = 100;
    private int $distributionWeight = 39;
    private int $consecutiveWeight = 30;
    private int $compactnessWeight = 30;     // Mapel tersebar ke terlalu banyak hari
    private int $dayPriorityWeight = 30;

    public function handle(): void
    {
        Cache::put('ga_status', 'running', 600);
        Cache::put('ga_generation', 0, 600);
        Cache::put('ga_fitness', 0, 600);
        Cache::put('ga_violations', 0, 600);
        Cache::put('ga_max_generations', $this->maxGenerations, 600);

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

        $evalContext = [
            'genes' => $genes,
            'slotMap' => $slotMap,
            'mapelMaxPerHari' => $mapelMaxPerHari,
            'mapelJamPerMinggu' => $mapelJamPerMinggu,
            'totalHari' => $totalHari,
            'slotsPerDay' => $slotsPerDay,
        ];

        // ── INITIAL POPULATION ──
        // 30% smart initialization, 70% biased random for diversity
        $population = [];
        $smartCount = (int) ($this->populationSize * 0.3);
        for ($i = 0; $i < $this->populationSize; $i++) {
            if ($i < $smartCount) {
                $population[] = $this->createSmartChromosome($totalGenes, $genes, $slotMap, $totalSlots);
            } else {
                $chromosome = [];
                for ($g = 0; $g < $totalGenes; $g++) {
                    $chromosome[] = $this->biasedRandSlot($totalSlots);
                }
                $population[] = $chromosome;
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
            Cache::put('ga_generation', $gen + 1, 600);
            Cache::put('ga_fitness', round($fitness, 6), 600);
            Cache::put('ga_violations', $hardViolations, 600);
            Cache::put('ga_dist_violations', $scores[$bestIdx]['dist_violations'], 600);

            // Debug log every 100 generations
            if ($gen % 100 === 0) {
                Log::info("GA Gen {$gen}: Score={$bestScore}, Guru={$scores[$bestIdx]['guru_conflicts']}, Kelas={$scores[$bestIdx]['kelas_conflicts']}, Dist={$scores[$bestIdx]['dist_violations']}, Cons={$scores[$bestIdx]['consecutive_violations']}, Compact={$scores[$bestIdx]['compactness_violations']}, DayPri={$scores[$bestIdx]['day_priority_penalty']}");
            }

            // Early stop
            if ($bestScore === 0) {
                break;
            }

            // Local search every 50 generations on best chromosome
            if ($gen % 50 === 0 && $gen > 0 && $bestChromosome) {
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
            : ['guru_conflicts' => -1, 'kelas_conflicts' => -1, 'dist_violations' => -1, 'consecutive_violations' => -1, 'total' => -1];

        $hard = $final['guru_conflicts'] + $final['kelas_conflicts'];

        Cache::put('ga_status', 'done', 600);
        Cache::put('ga_fitness', round(1.0 / (1.0 + $final['total']), 6), 600);
        Cache::put('ga_violations', $hard, 600);
        Cache::put('ga_dist_violations', $final['dist_violations'], 600);

        Log::info("GA DONE: Score={$final['total']}, Hard={$hard}, Dist={$final['dist_violations']}, Cons={$final['consecutive_violations']}, Compact={$final['compactness_violations']}, DayPri={$final['day_priority_penalty']}");

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

    /**
     * Greedy initialization: place genes without conflicts, preferring consecutive & early days.
     */
    private function createSmartChromosome(int $totalGenes, array $genes, array $slotMap, int $totalSlots): array
    {
        $chromosome = array_fill(0, $totalGenes, 0);
        $usedGuruSlots = [];
        $usedKelasSlots = [];

        // Track where each kelas-mapel is already placed: "kelasId-mapelId" => [hariIdx => [jamPos, ...]]
        $kelasMapelPlacements = [];

        // Shuffle gene order for diversity but process in batches by guru_mapel
        $indices = range(0, $totalGenes - 1);
        shuffle($indices);

        foreach ($indices as $g) {
            $gene = $genes[$g];
            $bestSlot = -1;
            $bestScore = -PHP_INT_MAX;

            $key = "{$gene['kelas_id']}-{$gene['mapel_id']}";

            foreach ($slotMap as $s => $slot) {
                // Skip if guru or kelas conflict
                if (isset($usedGuruSlots[$gene['guru_id']][$s]))
                    continue;
                if (isset($usedKelasSlots[$gene['kelas_id']][$s]))
                    continue;

                $score = 0;

                // Prefer earlier slots (earlier days fill first)
                $score += ($totalSlots - $s) * 0.1;

                // Big bonus for consecutive placement with same mapel on same day
                if (isset($kelasMapelPlacements[$key])) {
                    foreach ($kelasMapelPlacements[$key] as $hariIdx => $positions) {
                        if ($hariIdx === $slot['hari_idx']) {
                            foreach ($positions as $pos) {
                                if (abs($slot['jam_pos'] - $pos) === 1) {
                                    $score += 100; // Adjacent = huge bonus
                                } elseif (abs($slot['jam_pos'] - $pos) === 2) {
                                    $score += 20;  // Close = small bonus
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
                // Fallback: random (no conflict-free slot found)
                $bestSlot = rand(0, $totalSlots - 1);
            }

            $chromosome[$g] = $bestSlot;
            $usedGuruSlots[$gene['guru_id']][$bestSlot] = true;
            $usedKelasSlots[$gene['kelas_id']][$bestSlot] = true;
            $kelasMapelPlacements[$key][$slotMap[$bestSlot]['hari_idx']][] = $slotMap[$bestSlot]['jam_pos'];
        }

        return $chromosome;
    }

    /**
     * Biased random: prefers earlier slots.
     */
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
        $mapelJamPerMinggu = $ctx['mapelJamPerMinggu'];
        $totalHari = $ctx['totalHari'];
        $slotsPerDay = $ctx['slotsPerDay'];

        $guruConflicts = 0;
        $kelasConflicts = 0;
        $distViolations = 0;
        $consecutiveViolations = 0;
        $compactnessViolations = 0;
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

        // Soft: distribution + consecutive + compactness
        foreach ($kelasMapelHari as $key => $hariData) {
            $mapelId = (int) explode('-', $key)[1];
            $maxPerHari = $mapelMaxPerHari[$mapelId] ?? 2;
            $jamMinggu = $mapelJamPerMinggu[$mapelId] ?? 2;

            // Compactness: mapel should use minimum number of days
            // E.g., MTK 4 jam max 2/hari → ceil(4/2)=2 hari minimum
            $minDays = (int) ceil($jamMinggu / $maxPerHari);
            $actualDays = count($hariData);
            if ($actualDays > $minDays) {
                $compactnessViolations += ($actualDays - $minDays);
            }

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

        // Soft: day priority
        foreach ($kelasHariFill as $kelasId => $hariFills) {
            for ($d = 1; $d < $totalHari; $d++) {
                $prevFill = $hariFills[$d - 1] ?? 0;
                $currFill = $hariFills[$d] ?? 0;
                if ($currFill > $prevFill && $prevFill < $slotsPerDay) {
                    $dayPriorityPenalty += ($currFill - $prevFill);
                }
            }
        }

        $total = ($guruConflicts * $this->guruConflictWeight)
            + ($kelasConflicts * $this->kelasConflictWeight)
            + ($distViolations * $this->distributionWeight)
            + ($consecutiveViolations * $this->consecutiveWeight)
            + ($compactnessViolations * $this->compactnessWeight)
            + ($dayPriorityPenalty * $this->dayPriorityWeight);

        return [
            'guru_conflicts' => $guruConflicts,
            'kelas_conflicts' => $kelasConflicts,
            'dist_violations' => $distViolations,
            'consecutive_violations' => $consecutiveViolations,
            'compactness_violations' => $compactnessViolations,
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
    //  CROSSOVER (Uniform — better than one-point for scheduling)
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
    //  SMART MUTATION (conflict-directed)
    // ═══════════════════════════════════════════════════════════════

    private function smartMutate(array $chromosome, array $ctx, int $totalSlots): array
    {
        $genes = $ctx['genes'];
        $slotMap = $ctx['slotMap'];
        $geneCount = count($chromosome);

        // Find conflicting gene indices
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
            // Mutate 30-50% of conflicting genes toward conflict-free slots
            $mutCount = max(1, (int) (count($conflictIndices) * 0.4));
            shuffle($conflictIndices);

            for ($i = 0; $i < min($mutCount, count($conflictIndices)); $i++) {
                $idx = $conflictIndices[$i];

                // Build list of occupied slots for this gene's guru and kelas (excluding self)
                $blockedSlots = [];
                for ($g2 = 0; $g2 < $geneCount; $g2++) {
                    if ($g2 === $idx)
                        continue;
                    $s2 = $chromosome[$g2];
                    if ($genes[$g2]['guru_id'] === $genes[$idx]['guru_id'] || $genes[$g2]['kelas_id'] === $genes[$idx]['kelas_id']) {
                        $blockedSlots[$s2] = true;
                    }
                }

                // Find available slots
                $available = [];
                for ($s = 0; $s < $totalSlots; $s++) {
                    if (!isset($blockedSlots[$s])) {
                        $available[] = $s;
                    }
                }

                if (!empty($available)) {
                    // Prefer earlier slots among available
                    $picked = $available[0];
                    if (count($available) > 3) {
                        // Pick from top 30% (early bias)
                        $topN = max(1, (int) (count($available) * 0.3));
                        $picked = $available[rand(0, $topN - 1)];
                    }
                    $chromosome[$idx] = $picked;
                }
            }
        } else {
            // No conflicts: apply small random mutation for exploration
            for ($g = 0; $g < $geneCount; $g++) {
                if ($this->randFloat() < $this->mutationRate) {
                    $chromosome[$g] = $this->biasedRandSlot($totalSlots);
                }
            }
        }

        return $chromosome;
    }

    // ═══════════════════════════════════════════════════════════════
    //  LOCAL SEARCH (Hill Climbing on best chromosome)
    // ═══════════════════════════════════════════════════════════════

    private function localSearch(array $chromosome, array $ctx): array
    {
        $genes = $ctx['genes'];
        $slotMap = $ctx['slotMap'];
        $bestScore = $this->evaluate($chromosome, $ctx)['total'];
        $bestChromosome = $chromosome;
        $totalSlots = count($slotMap);

        // Strategy 1: Swap slots between pairs of conflicting genes
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

        // Try moving each conflicting gene to a better slot
        foreach ($conflictPairs as [$g1, $g2]) {
            // Try relocating g2 to each possible slot
            for ($s = 0; $s < $totalSlots; $s++) {
                if ($s === $chromosome[$g2])
                    continue;
                $trial = $bestChromosome;
                $trial[$g2] = $s;
                $trialScore = $this->evaluate($trial, $ctx)['total'];
                if ($trialScore < $bestScore) {
                    $bestScore = $trialScore;
                    $bestChromosome = $trial;
                }
            }
            // Early exit if perfect
            if ($bestScore === 0)
                return $bestChromosome;
        }

        // Strategy 2: Improve consecutive placement
        // Group genes by kelas-mapel and try swapping within groups
        $genesByKelasMapel = [];
        for ($g = 0; $g < count($genes); $g++) {
            $key = "{$genes[$g]['kelas_id']}-{$genes[$g]['mapel_id']}";
            $genesByKelasMapel[$key][] = $g;
        }

        foreach ($genesByKelasMapel as $indices) {
            if (count($indices) < 2)
                continue;

            for ($i = 0; $i < count($indices) - 1; $i++) {
                for ($j = $i + 1; $j < count($indices); $j++) {
                    $trial = $bestChromosome;
                    $temp = $trial[$indices[$i]];
                    $trial[$indices[$i]] = $trial[$indices[$j]];
                    $trial[$indices[$j]] = $temp;

                    $trialScore = $this->evaluate($trial, $ctx)['total'];
                    if ($trialScore < $bestScore) {
                        $bestScore = $trialScore;
                        $bestChromosome = $trial;
                    }
                }
            }
            if ($bestScore === 0)
                return $bestChromosome;
        }

        return $bestChromosome;
    }

    // ═══════════════════════════════════════════════════════════════

    private function randFloat(): float
    {
        return mt_rand() / mt_getrandmax();
    }
}
