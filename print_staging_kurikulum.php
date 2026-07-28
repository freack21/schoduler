<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Kurikulum;

$kuri = Kurikulum::with(['tingkat', 'jurusan', 'mapel'])->get();
echo "=== SEMUA KURIKULUM MAPPING DI SERVER ===\n";
foreach ($kuri as $k) {
    $t = $k->tingkat->nama ?? 'NULL';
    $j = $k->jurusan->nama ?? 'Semua';
    $m = $k->mapel->nama ?? 'NULL';
    $hours = $k->mapel->jam_per_minggu ?? 0;
    echo "- Tingkat: {$t} | Jurusan: {$j} | Mapel: {$m} ({$hours} jam) [ID Kurikulum: {$k->id}]\n";
}
