<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\GuruMapel;
use App\Models\Mapel;

$fisikaX = Mapel::where('nama', 'Fisika')->first();
$fisikaLanjut = Mapel::where('nama', 'Fisika Lanjutan')->first();

echo "=== GURU MAPEL FISIKA (TINGKAT X) ===\n";
if ($fisikaX) {
    $mappings = GuruMapel::where('mapel_id', $fisikaX->id)->with(['tingkat', 'guru.user'])->get();
    foreach ($mappings as $m) {
        $tName = $m->tingkat->nama ?? 'NULL';
        $gName = $m->guru->user->nama_lengkap ?? $m->guru->nama;
        echo "- Guru: {$gName} (ID: {$m->guru_id}) | Tingkat: {$tName}\n";
    }
}

echo "\n=== GURU MAPEL FISIKA LANJUTAN (TINGKAT XI & XII) ===\n";
if ($fisikaLanjut) {
    $mappings = GuruMapel::where('mapel_id', $fisikaLanjut->id)->with(['tingkat', 'guru.user'])->get();
    foreach ($mappings as $m) {
        $tName = $m->tingkat->nama ?? 'NULL';
        $gName = $m->guru->user->nama_lengkap ?? $m->guru->nama;
        echo "- Guru: {$gName} (ID: {$m->guru_id}) | Tingkat: {$tName}\n";
    }
}
