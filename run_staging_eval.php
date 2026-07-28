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

echo "=== DIAGNOSTIK GENERATOR DATA & INITIAL SMART CHROMOSOME ===\n";

$jamList = JamPelajaran::all();
$allDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$dbDays = $jamList->pluck('hari')->unique()->toArray();
$hariAktif = array_values(array_intersect($allDays, $dbDays));

echo "Hari Aktif: " . implode(', ', $hariAktif) . "\n";

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
echo "Total Slot KBM Mingguan: {$totalSlots}\n";

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

echo "Total Kelas: " . $kelasList->count() . "\n";
echo "Total Kurikulum Mapping: " . $kurikulumList->count() . "\n";
echo "Total Guru Mapel Mapping: " . $guruMapelAll->count() . "\n";

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

echo "Total Demands: " . count($demands) . "\n";

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

echo "Total Blocks: " . count($blocks) . "\n";

// Assign gurunya dengan Least-Loaded First
$assignedGurus = [];
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
    $assignedGurus[$dIdx] = $picked;
}

// Simulasi Heuristik (Smart Chromosome)
$usedGuruSlots = [];
$usedKelasSlots = [];
$bIndices = array_keys($blocks);
usort($bIndices, fn($a, $b) => $blocks[$b]['size'] <=> $blocks[$a]['size']);

$slots = [];
$kelasConflicts = 0;
$guruConflicts = 0;

foreach ($bIndices as $bIdx) {
    $block = $blocks[$bIdx];
    $dIdx = $block['demand_idx'];
    $demand = $demands[$dIdx];
    $guruMap = $assignedGurus[$dIdx];
    $kelasId = $demand['kelas_id'];
    $size = $block['size'];

    $valid = $validBlockStarts[$size];
    $bestSlot = $valid[0];
    $minC = PHP_INT_MAX;

    foreach ($valid as $sSlot) {
        $c = 0;
        for ($i = 0; $i < $size; $i++) {
            $sIdx = $sSlot + $i;
            foreach ($guruMap as $guruId) {
                if (isset($usedGuruSlots[$guruId][$sIdx])) $c++;
            }
            if (isset($usedKelasSlots[$kelasId][$sIdx])) $c++;
        }
        if ($c < $minC) {
            $minC = $c;
            $bestSlot = $sSlot;
        }
        if ($c === 0) break;
    }

    $slots[$bIdx] = $bestSlot;
    for ($i = 0; $i < $size; $i++) {
        $sIdx = $bestSlot + $i;
        foreach ($guruMap as $guruId) {
            if (isset($usedGuruSlots[$guruId][$sIdx])) $guruConflicts++;
            $usedGuruSlots[$guruId][$sIdx] = true;
        }
        if (isset($usedKelasSlots[$kelasId][$sIdx])) $kelasConflicts++;
        $usedKelasSlots[$kelasId][$sIdx] = true;
    }
}

echo "Smart Chromosome Simulation Result:\n";
echo "   Guru Conflicts: {$guruConflicts}\n";
echo "   Kelas Conflicts: {$kelasConflicts}\n";
echo "   Total Hard Conflicts: " . ($guruConflicts + $kelasConflicts) . "\n";
