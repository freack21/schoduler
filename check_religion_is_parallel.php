<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Mapel;

$parallelMapels = Mapel::where('is_parallel', true)->get();
echo "=== PARALLEL MAPELS ===\n";
foreach ($parallelMapels as $m) {
    echo "- Mapel: {$m->nama} (ID: {$m->id}) | kelompok_paralel: '{$m->kelompok_paralel}'\n";
}
