<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\GuruMapel;
use App\Models\Guru;

$kelasList = Kelas::with(['tingkat', 'jurusan'])->get();
$kurikulumList = Kurikulum::with('mapel')->get();
$guruMapelAll = GuruMapel::all();

// Simulasi pemilihan guru statis seperti di GenerateScheduleJob
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

// Assign gurunya dengan Least-Loaded First
$assignedGurus = [];
$guruLoad = [];
$dIndices = array_keys($demands);
usort($dIndices, function($a, $b) use ($demands) {
    $countA = array_sum(array_map('count', $demands[$a]['eligible_gurus']));
    $countB = array_sum(array_map('count', $demands[$b]['eligible_gurus']));
    return $countA <=> $countB;
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

echo "=== BEBAN MENGAJAR GURU (TOTAL JAM HARUS <= 44 JAM) ===\n";
arsort($guruLoad);
$overloadCount = 0;
foreach ($guruLoad as $gid => $load) {
    $g = Guru::with('user')->find($gid);
    $name = $g->user->nama_lengkap ?? $g->nama;
    $status = $load > 44 ? "🚨 OVERLOAD (BENTROK PASTI TERJADI!)" : "✅ AMAN";
    echo "Guru: {$name} | Beban: {$load} jam/minggu | Status: {$status}\n";
    if ($load > 44) $overloadCount++;
}

if ($overloadCount === 0) {
    echo "\n🎉 SEMUA BEBAN GURU AMAN! Secara beban total harusnya aman.\n";
} else {
    echo "\n🚨 ADA GURU YANG OVERLOAD! Kurikulum harus ditata ulang agar beban guru ini berkurang.\n";
}
