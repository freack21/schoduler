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

class GenerateScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // GA Parameters
    private int $populationSize = 100;
    private int $maxGenerations = 1000;
    private float $crossoverRate = 0.8;
    private float $mutationRate = 0.1;
    private int $eliteCount = 2;
    private int $tournamentSize = 3;

    // Penalty weights for fitness
    private int $guruConflictWeight = 10;    // Guru bentrok (hard constraint)
    private int $kelasConflictWeight = 10;   // Kelas bentrok (hard constraint)
    private int $distributionWeight = 3;     // Persebaran mapel melebihi max/hari (soft constraint)
    private int $consecutiveWeight = 5;      // Mapel tidak berurutan dalam hari (soft constraint)
    private int $dayPriorityWeight = 1;      // Hari awal lebih prioritas (soft constraint)

    public function handle(): void
    {
        Cache::put('ga_status', 'running', 600);
        Cache::put('ga_generation', 0, 600);
        Cache::put('ga_fitness', 0, 600);
        Cache::put('ga_violations', 0, 600);
        Cache::put('ga_max_generations', $this->maxGenerations, 600);

        // Load data
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
        foreach ($guruMapels as $gm) {
            $mapelMaxPerHari[$gm->mapel_id] = $gm->mapel->max_jam_per_hari;
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

        // Only use non-istirahat slots
        $jamAktif = $jamPelajaranList->where('is_istirahat', false);
        $jamIds = $jamAktif->pluck('id')->toArray();

        // Build jam_ke ordering map for consecutive check (jam_id => position index, skipping istirahat)
        $jamPositionMap = []; // jam_pelajaran_id => consecutive position index (0, 1, 2, ...)
        $pos = 0;
        foreach ($jamPelajaranList as $jp) {
            if (!$jp->is_istirahat) {
                $jamPositionMap[$jp->id] = $pos;
                $pos++;
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
                    'jam_pos' => $jamPositionMap[$jamId], // consecutive position (ignoring istirahat)
                ];
            }
        }
        $totalSlots = count($slotMap);

        if ($totalSlots === 0) {
            Cache::put('ga_status', 'error', 600);
            Cache::put('ga_message', 'Tidak ada slot waktu tersedia.', 600);
            return;
        }

        // Compute total non-istirahat slots per kelas per day
        $slotsPerDay = count($jamIds);
        $totalHari = count($hariAktif);

        // Generate initial population (bias toward earlier days/slots)
        $population = [];
        for ($i = 0; $i < $this->populationSize; $i++) {
            $chromosome = [];
            for ($g = 0; $g < $totalGenes; $g++) {
                // Bias: prefer lower slot indices (earlier days, earlier jam)
                $chromosome[] = $this->biasedRandSlot($totalSlots);
            }
            $population[] = $chromosome;
        }

        $bestChromosome = null;
        $bestScore = PHP_INT_MAX;

        $evalContext = [
            'genes' => $genes,
            'slotMap' => $slotMap,
            'mapelMaxPerHari' => $mapelMaxPerHari,
            'totalHari' => $totalHari,
            'slotsPerDay' => $slotsPerDay,
        ];

        for ($gen = 0; $gen < $this->maxGenerations; $gen++) {
            $scores = [];
            foreach ($population as $chromosome) {
                $scores[] = $this->evaluate($chromosome, $evalContext);
            }

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

            $fitness = 1.0 / (1.0 + $bestScore);
            $hardViolations = $scores[$bestIdx]['guru_conflicts'] + $scores[$bestIdx]['kelas_conflicts'];
            Cache::put('ga_generation', $gen + 1, 600);
            Cache::put('ga_fitness', round($fitness, 6), 600);
            Cache::put('ga_violations', $hardViolations, 600);
            Cache::put('ga_dist_violations', $scores[$bestIdx]['dist_violations'], 600);

            if ($bestScore === 0)
                break;

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
                    [$c1, $c2] = $this->crossover($p1, $p2);
                } else {
                    $c1 = $p1;
                    $c2 = $p2;
                }

                $c1 = $this->mutate($c1, $totalSlots);
                $c2 = $this->mutate($c2, $totalSlots);

                $newPop[] = $c1;
                if (count($newPop) < $this->populationSize)
                    $newPop[] = $c2;
            }

            $population = $newPop;
        }

        // Save result
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
            : ['guru_conflicts' => -1, 'kelas_conflicts' => -1, 'dist_violations' => -1, 'total' => -1];

        $hard = $final['guru_conflicts'] + $final['kelas_conflicts'];

        Cache::put('ga_status', 'done', 600);
        Cache::put('ga_fitness', round(1.0 / (1.0 + $final['total']), 6), 600);
        Cache::put('ga_violations', $hard, 600);
        Cache::put('ga_dist_violations', $final['dist_violations'], 600);

        if ($hard === 0 && $final['dist_violations'] === 0) {
            Cache::put('ga_message', 'Jadwal berhasil digenerate tanpa bentrok dan persebaran optimal!', 600);
        } elseif ($hard === 0) {
            Cache::put('ga_message', "Jadwal tanpa bentrok! Soft violations: {$final['dist_violations']} distribusi, {$final['consecutive_violations']} urutan.", 600);
        } else {
            Cache::put('ga_message', "Jadwal digenerate dengan {$hard} bentrok.", 600);
        }
    }

    /**
     * Biased random: prefer earlier slots (earlier days fill first).
     */
    private function biasedRandSlot(int $totalSlots): int
    {
        // Use squared distribution to bias toward lower indices
        $r = $this->randFloat();
        return (int) floor($r * $r * $totalSlots) % $totalSlots;
    }

    /**
     * Evaluate chromosome fitness.
     */
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
        // kelas-mapel per hari: "{kelas_id}-{mapel_id}" => [hari_idx => [jam_pos1, jam_pos2, ...]]
        $kelasMapelHari = [];
        // Track kelas per hari fill count for day priority
        $kelasHariFill = []; // kelas_id => [hari_idx => count]

        for ($g = 0; $g < count($chromosome); $g++) {
            $slotIdx = $chromosome[$g];
            $guruId = $genes[$g]['guru_id'];
            $kelasId = $genes[$g]['kelas_id'];
            $mapelId = $genes[$g]['mapel_id'];
            $hariIdx = $slotMap[$slotIdx]['hari_idx'];
            $jamPos = $slotMap[$slotIdx]['jam_pos'];

            // Hard: Guru conflict
            if (isset($guruSlots[$guruId][$slotIdx]))
                $guruConflicts++;
            $guruSlots[$guruId][$slotIdx] = true;

            // Hard: Kelas conflict
            if (isset($kelasSlots[$kelasId][$slotIdx]))
                $kelasConflicts++;
            $kelasSlots[$kelasId][$slotIdx] = true;

            // Track mapel positions per day per kelas
            $key = "{$kelasId}-{$mapelId}";
            $kelasMapelHari[$key][$hariIdx][] = $jamPos;

            // Track fill per day per kelas
            $kelasHariFill[$kelasId][$hariIdx] = ($kelasHariFill[$kelasId][$hariIdx] ?? 0) + 1;
        }

        // Soft: Distribution + Consecutive check
        foreach ($kelasMapelHari as $key => $hariData) {
            $mapelId = (int) explode('-', $key)[1];
            $maxPerHari = $mapelMaxPerHari[$mapelId] ?? 2;

            foreach ($hariData as $hariIdx => $positions) {
                $count = count($positions);

                // Distribution: too many hours of same mapel on same day
                if ($count > $maxPerHari) {
                    $distViolations += ($count - $maxPerHari);
                }

                // Consecutive: positions should be consecutive
                if ($count > 1) {
                    sort($positions);
                    for ($i = 1; $i < count($positions); $i++) {
                        if ($positions[$i] !== $positions[$i - 1] + 1) {
                            $consecutiveViolations++;
                        }
                    }
                }
            }
        }

        // Soft: Day priority — earlier days should have more lessons
        // Penalize if a later day has MORE lessons than an earlier day for the same kelas
        foreach ($kelasHariFill as $kelasId => $hariFills) {
            for ($d = 1; $d < $totalHari; $d++) {
                $prevFill = $hariFills[$d - 1] ?? 0;
                $currFill = $hariFills[$d] ?? 0;
                if ($currFill > $prevFill && $prevFill < $slotsPerDay) {
                    // Later day has more lessons but earlier day isn't full yet
                    $dayPriorityPenalty += ($currFill - $prevFill);
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

    private function tournamentSelect(array $pop, array $fit): array
    {
        $best = rand(0, count($pop) - 1);
        for ($i = 1; $i < $this->tournamentSize; $i++) {
            $idx = rand(0, count($pop) - 1);
            if ($fit[$idx] > $fit[$best])
                $best = $idx;
        }
        return $pop[$best];
    }

    private function crossover(array $p1, array $p2): array
    {
        $pt = rand(1, count($p1) - 2);
        return [
            array_merge(array_slice($p1, 0, $pt), array_slice($p2, $pt)),
            array_merge(array_slice($p2, 0, $pt), array_slice($p1, $pt)),
        ];
    }

    private function mutate(array $c, int $totalSlots): array
    {
        for ($i = 0; $i < count($c); $i++) {
            if ($this->randFloat() < $this->mutationRate) {
                $c[$i] = $this->biasedRandSlot($totalSlots);
            }
        }
        return $c;
    }

    private function randFloat(): float
    {
        return mt_rand() / mt_getrandmax();
    }
}
