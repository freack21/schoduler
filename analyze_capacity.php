<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$jamList = App\Models\JamPelajaran::all();
$totalJam = 0;
foreach($jamList as $j) {
    if (!$j->is_istirahat && $j->jam_ke != 0) {
        $totalJam++;
    }
}
echo "Total Slot (Aktif): " . $totalJam . "\n\n";

$kelas = App\Models\Kelas::with(['tingkat', 'jurusan'])->get();
$kurikulumAll = App\Models\Kurikulum::with('mapel')->get();

foreach($kelas as $k) {
    $kuriList = $kurikulumAll->where('tingkat_id', $k->tingkat_id);
    if ($k->jurusan_id) {
        $kuriList = $kuriList->filter(function($kuri) use ($k) {
            return is_null($kuri->jurusan_id) || $kuri->jurusan_id == $k->jurusan_id;
        });
    } else {
        $kuriList = $kuriList->whereNull('jurusan_id');
    }
    
    $jam = $kuriList->sum(function($k) {
        return $k->mapel ? $k->mapel->jam_per_minggu : 0;
    });
    
    // Group parallel mapel to avoid double counting their hours
    $parallel = $kuriList->filter(function($kuri) {
        return $kuri->mapel && $kuri->mapel->is_parallel == 1;
    });
    
    // Group by kelompok parallel
    $parallelGroups = $parallel->groupBy(function($item) {
        return $item->mapel->kelompok_paralel ?? ('id_' . $item->mapel->id);
    });
    $parallelDiscount = 0;
    foreach($parallelGroups as $group => $items) {
        // Only one of them counts towards actual slot usage
        // Wait, they all have the same jam_per_minggu. We should subtract all except one.
        $first = $items->first();
        $discount = $items->sum(function($k){ return $k->mapel ? $k->mapel->jam_per_minggu : 0; }) - ($first->mapel ? $first->mapel->jam_per_minggu : 0);
        $parallelDiscount += $discount;
    }
    
    $actualJam = $jam - $parallelDiscount;
    $sisa = $totalJam - $actualJam;
    
    echo $k->nama . " - Butuh: " . $actualJam . " slot | Sisa (Kosong): " . $sisa . " slot\n";
}
