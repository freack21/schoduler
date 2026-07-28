<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\GuruMapel;
use App\Models\JamPelajaran;
use App\Models\Mapel;

echo "=== MEMULAI TEST GA DENGAN GURU & SLOT DINAMIS ===\n";

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

$validBlockStarts = [];
for ($size = 1; $size <= 4; $size++) {
    $validBlockStarts[$size] = [];
    for ($slot = 0; $slot <= $totalSlots - $size; $slot++) {
        if ($slotMap[$slot]['hari_idx'] === $slotMap[$slot + $size - 1]['hari_idx']) {
            $validBlockStarts[$size][] = $slot;
        }
    }
}

$kelasList = Kelas::with(['jurusan', 'tingkat'])->get();
$kurikulumList = Kurikulum::with('mapel')->get();
$guruMapelAll = GuruMapel::all();

$demands = [];
foreach ($kelasList as $k) {
    $kuriList = $kurikulumList->where('tingkat_id', $k->tingkat_id);
    if ($k->jurusan_id) {
        $kuriList = $kuriList->filter(fn($kuri) => is_null($kuri->jurusan_id) || $kuri->jurusan_id == $k->jurusan_id);
    } else {
        $kuriList = $kuriList->whereNull('jurusan_id');
    }

    $kelasDemands = [];
    foreach ($kuriList as $kuri) {
        if (!$kuri->mapel) continue;
        $eligibleGurus = $guruMapelAll->where('mapel_id', $kuri->mapel_id)
            ->where('tingkat_id', $k->tingkat_id)
            ->filter(fn($gm) => is_null($gm->jurusan_id) || $gm->jurusan_id == $k->jurusan_id)
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
                'jam_per_minggu' => $kuri->mapel->jam_per_minggu,
                'jam_per_hari' => $kuri->mapel->jam_per_hari,
                'eligible_gurus' => [
                    $kuri->mapel_id => array_values($eligibleGurus)
                ]
            ];
        }
    }
    foreach ($kelasDemands as $kd) {
        $demands[] = $kd;
    }
}

$blocks = [];
foreach ($demands as $dIdx => $demand) {
    $sisa = $demand['jam_per_minggu'];
    $maxPerHari = $demand['jam_per_hari'];
    while ($sisa > 0) {
        $maxPH = (empty($maxPerHari) || $maxPerHari <= 0) ? $sisa : $maxPerHari;
        $take = min($sisa, $maxPH);
        $blocks[] = [
            'demand_idx' => $dIdx,
            'size' => $take
        ];
        $sisa -= $take;
    }
}

$ctx = [
    'demands' => $demands,
    'blocks' => $blocks,
    'slotMap' => $slotMap,
    'validBlockStarts' => $validBlockStarts,
];

function evaluate($chrom, $ctx) {
    $demands = $ctx['demands'];
    $blocks = $ctx['blocks'];
    $slotMap = $ctx['slotMap'];
    
    $guruSlots = [];
    $kelasSlots = [];
    $guruConflicts = 0;
    $kelasConflicts = 0;
    
    foreach ($blocks as $bIdx => $block) {
        $dIdx = $block['demand_idx'];
        $demand = $demands[$dIdx];
        $start = $chrom['slots'][$bIdx];
        $size = $block['size'];
        $guruMap = $chrom['teachers'][$dIdx];
        $kelasId = $demand['kelas_id'];
        
        for ($i = 0; $i < $size; $i++) {
            $sIdx = $start + $i;
            foreach ($guruMap as $guruId) {
                if (isset($guruSlots[$guruId][$sIdx])) $guruConflicts++;
                $guruSlots[$guruId][$sIdx] = true;
            }
            if (isset($kelasSlots[$kelasId][$sIdx])) $kelasConflicts++;
            $kelasSlots[$kelasId][$sIdx] = true;
        }
    }
    
    return [
        'guru' => $guruConflicts,
        'kelas' => $kelasConflicts,
        'total' => $guruConflicts + $kelasConflicts
    ];
}

function createSmartChromosome($ctx) {
    $demands = $ctx['demands'];
    $blocks = $ctx['blocks'];
    $validBlockStarts = $ctx['validBlockStarts'];
    
    $teachers = [];
    $guruLoad = [];
    
    $dIndices = array_keys($demands);
    usort($dIndices, function($a, $b) use ($demands) {
        return count($demands[$a]['eligible_gurus']) <=> count($demands[$b]['eligible_gurus']);
    });
    
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
        $teachers[$dIdx] = $picked;
    }
    
    $slots = [];
    $usedGuruSlots = [];
    $usedKelasSlots = [];
    $bIndices = array_keys($blocks);
    usort($bIndices, fn($a, $b) => $blocks[$b]['size'] <=> $blocks[$a]['size']);
    
    foreach ($bIndices as $bIdx) {
        $block = $blocks[$bIdx];
        $dIdx = $block['demand_idx'];
        $demand = $demands[$dIdx];
        $guruMap = $teachers[$dIdx];
        $kelasId = $demand['kelas_id'];
        $size = $block['size'];
        
        $validStarts = $validBlockStarts[$size];
        $bestSlot = $validStarts[0];
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
            if ($conflicts < $minConflicts) {
                $minConflicts = $conflicts;
                $bestSlot = $s;
            }
            if ($conflicts === 0) break;
        }
        
        $slots[$bIdx] = $bestSlot;
        for ($i = 0; $i < $size; $i++) {
            $sIdx = $bestSlot + $i;
            foreach ($guruMap as $guruId) {
                $usedGuruSlots[$guruId][$sIdx] = true;
            }
            $usedKelasSlots[$kelasId][$sIdx] = true;
        }
    }
    
    return ['slots' => $slots, 'teachers' => $teachers];
}

// Mutate teachers & slots
function mutate($chrom, $ctx, $rate = 0.1) {
    $demands = $ctx['demands'];
    $blocks = $ctx['blocks'];
    $validBlockStarts = $ctx['validBlockStarts'];
    
    // Mutate slots
    foreach ($blocks as $bIdx => $block) {
        if ((mt_rand() / mt_getrandmax()) < $rate) {
            $valid = $validBlockStarts[$block['size']];
            $chrom['slots'][$bIdx] = $valid[array_rand($valid)];
        }
    }
    
    // Mutate teachers
    foreach ($demands as $dIdx => $demand) {
        if ((mt_rand() / mt_getrandmax()) < $rate) {
            foreach ($demand['eligible_gurus'] as $mId => $eligible) {
                if (count($eligible) > 1) {
                    $chrom['teachers'][$dIdx][$mId] = $eligible[array_rand($eligible)];
                }
            }
        }
    }
    return $chrom;
}

// Run simple GA
$pop = [];
$popSize = 50;
for ($i = 0; $i < $popSize; $i++) {
    $pop[] = createSmartChromosome($ctx);
}

echo "Running GA...\n";
for ($gen = 1; $gen <= 40; $gen++) {
    $scores = [];
    foreach ($pop as $idx => $chrom) {
        $eval = evaluate($chrom, $ctx);
        $scores[$idx] = $eval['total'];
    }
    
    asort($scores);
    $bestIdx = array_key_first($scores);
    $bestScore = $scores[$bestIdx];
    
    echo "Gen {$gen} | Best Hard Conflicts: {$bestScore}\n";
    if ($bestScore === 0) {
        echo "🎉 SUCCESS! Bentrok mencapai 0!\n";
        break;
    }
    
    // Selection and reproduction
    $newPop = [];
    $bestChroms = array_keys($scores);
    for ($i = 0; $i < 5; $i++) {
        $newPop[] = $pop[$bestChroms[$i]];
    }
    
    while (count($newPop) < $popSize) {
        $parent = $pop[$bestChroms[rand(0, 15)]];
        $newPop[] = mutate($parent, $ctx, 0.1);
    }
    $pop = $newPop;
}
