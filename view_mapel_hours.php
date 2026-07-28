<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Mapel;

$mapels = Mapel::all();
echo "=== JAM PER HARI DETAIL MAPEL DI SERVER ===\n";
foreach ($mapels as $m) {
    echo "- Nama: {$m->nama} | Jam/Minggu: {$m->jam_per_minggu} | Jam/Hari: {$m->jam_per_hari}\n";
}
