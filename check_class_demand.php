<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Kelas;
use App\Models\Kurikulum;

$k = Kelas::where('nama', 'XI-2')->first();
echo "=== DETAIL KELAS {$k->nama} ===\n";
echo "ID: {$k->id} | Tingkat: {$k->tingkat->nama} | Jurusan: " . ($k->jurusan->nama ?? 'Semua') . "\n";

$kuri = Kurikulum::where('tingkat_id', $k->tingkat_id)
    ->where(function($q) use ($k) {
        $q->where('jurusan_id', $k->jurusan_id)
          ->orWhereNull('jurusan_id');
    })
    ->with('mapel')
    ->get();

$total = 0;
foreach ($kuri as $item) {
    if ($item->mapel) {
        echo "- Mapel: {$item->mapel->nama} (ID: {$item->mapel->id}) | Jam: {$item->mapel->jam_per_minggu} jam | Parallel: " . ($item->mapel->is_parallel ? 'YA' : 'TIDAK') . "\n";
        $total += $item->mapel->jam_per_minggu;
    }
}
echo "TOTAL LOAD JAM PELAJARAN: {$total} jam\n";
