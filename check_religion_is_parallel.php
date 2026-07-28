<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Mapel;

$pai = Mapel::where('nama', 'like', '%Agama Islam%')->first();
$kristen = Mapel::where('nama', 'like', '%Agama Kristen%')->first();

echo "=== DETAIL MAPEL AGAMA ===\n";
if ($pai) {
    echo "- Mapel: {$pai->nama} (ID: {$pai->id}) | is_parallel: " . ($pai->is_parallel ? 'TRUE' : 'FALSE') . " | kelompok_paralel: '{$pai->kelompok_paralel}'\n";
} else {
    echo "- PAI tidak ditemukan!\n";
}

if ($kristen) {
    echo "- Mapel: {$kristen->nama} (ID: {$kristen->id}) | is_parallel: " . ($kristen->is_parallel ? 'TRUE' : 'FALSE') . " | kelompok_paralel: '{$kristen->kelompok_paralel}'\n";
} else {
    echo "- Kristen tidak ditemukan!\n";
}
