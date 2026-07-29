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

$kelas = App\Models\Kelas::all();
foreach($kelas as $k) {
    $kuri = App\Models\Kurikulum::where('kelas_id', $k->id)->get();
    $jam = $kuri->sum('jam_per_minggu');
    // Kurangi jam untuk mapel is_parallel karena gabung
    $parallel = App\Models\Kurikulum::where('kelas_id', $k->id)
        ->join('mapel', 'mapel.id', '=', 'kurikulum.mapel_id')
        ->where('mapel.is_parallel', 1)
        ->get();
    
    // Group by kelompok parallel
    $parallelGroups = $parallel->groupBy('kelompok_paralel');
    $parallelDiscount = 0;
    foreach($parallelGroups as $group => $items) {
        // Only one of them counts towards actual slot usage
        // Wait, they all have the same jam_per_minggu. We should subtract all except one.
        $first = $items->first();
        $discount = $items->sum('jam_per_minggu') - $first->jam_per_minggu;
        $parallelDiscount += $discount;
    }
    
    $actualJam = $jam - $parallelDiscount;
    $sisa = $totalJam - $actualJam;
    
    echo $k->nama . " - Butuh: " . $actualJam . " slot | Sisa (Kosong): " . $sisa . " slot\n";
}
