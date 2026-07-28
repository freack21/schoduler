<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\GuruMapel;
use App\Models\Mapel;
use App\Models\Guru;

$pai = Mapel::where('nama', 'Pendidikan Agama Islam')->first();
$kristen = Mapel::where('nama', 'Pendidikan Agama Kristen')->first();

echo "=== GURU MAPEL AGAMA ISLAM (ID: " . ($pai->id ?? 'NULL') . ") ===\n";
if ($pai) {
    $mappings = GuruMapel::where('mapel_id', $pai->id)->with(['tingkat', 'guru.user'])->get();
    foreach ($mappings as $m) {
        $tName = $m->tingkat->nama ?? 'NULL';
        $gName = $m->guru->user->nama_lengkap ?? $m->guru->nama;
        echo "- Guru: {$gName} (ID: {$m->guru_id}) | Tingkat: {$tName}\n";
    }
}

echo "\n=== GURU MAPEL AGAMA KRISTEN (ID: " . ($kristen->id ?? 'NULL') . ") ===\n";
if ($kristen) {
    $mappings = GuruMapel::where('mapel_id', $kristen->id)->with(['tingkat', 'guru.user'])->get();
    foreach ($mappings as $m) {
        $tName = $m->tingkat->nama ?? 'NULL';
        $gName = $m->guru->user->nama_lengkap ?? $m->guru->nama;
        echo "- Guru: {$gName} (ID: {$m->guru_id}) | Tingkat: {$tName}\n";
    }
}
