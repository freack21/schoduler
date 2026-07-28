<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\GuruMapel;
use App\Models\JamPelajaran;

$kelasList = Kelas::with(['jurusan', 'tingkat'])->get();
$kurikulumList = Kurikulum::with('mapel')->get();
$guruMapelAll = GuruMapel::all();

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
        $slotMap[$s] = $jam;
        $s++;
        $jIdx++;
    }
}
$totalSlots = count($slotMap);

echo "=== TOTAL ACTIVE SLOTS IN WEEK: {$totalSlots} ===\n\n";

foreach ($kelasList as $k) {
    $kuriList = $kurikulumList->where('tingkat_id', $k->tingkat_id);
    if ($k->jurusan_id) {
        $kuriList = $kuriList->filter(fn($kuri) => is_null($kuri->jurusan_id) || $kuri->jurusan_id == $k->jurusan_id);
    } else {
        $kuriList = $kuriList->whereNull('jurusan_id');
    }

    $totalHours = 0;
    $kelasDemands = [];
    
    foreach ($kuriList as $kuri) {
        if (!$kuri->mapel) continue;
        $eligibleGurus = $guruMapelAll->where('mapel_id', $kuri->mapel_id)
            ->where('tingkat_id', $k->tingkat_id)
            ->filter(fn($gm) => is_null($gm->jurusan_id) || $gm->jurusan_id == $k->jurusan_id);
            
        if ($eligibleGurus->isEmpty()) continue;

        if ($kuri->mapel->is_parallel) {
            $kelompok = $kuri->mapel->kelompok_paralel ?: ('id_' . $kuri->mapel->id);
            $key = 'parallel_' . md5($kelompok);
            if (!isset($kelasDemands[$key])) {
                $kelasDemands[$key] = $kuri->mapel->jam_per_minggu;
            }
        } else {
            $totalHours += $kuri->mapel->jam_per_minggu;
        }
    }
    
    foreach ($kelasDemands as $hours) {
        $totalHours += $hours;
    }
    
    $diff = $totalHours - $totalSlots;
    $status = $diff > 0 ? "🚨 OVERLOAD BY {$diff} HOURS!" : "✅ SAFE ({$totalHours} hours)";
    echo "- Kelas: {$k->nama} | Total Jam Pelajaran: {$totalHours} jam | {$status}\n";
}
