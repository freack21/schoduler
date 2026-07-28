<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Mapel;
use App\Models\GuruMapel;
use App\Models\Tingkat;

$tingkats = Tingkat::all();
$mapels = Mapel::all();

echo "=== MENCARI SUBJEK DENGAN HANYA 1 GURU PENGAMPU ===\n";
foreach ($tingkats as $t) {
    echo "\n Tingkat: {$t->nama} \n";
    foreach ($mapels as $m) {
        $mappings = GuruMapel::where('mapel_id', $m->id)
            ->where('tingkat_id', $t->id)
            ->with('guru.user')
            ->get();
            
        if ($mappings->count() === 1) {
            $gName = $mappings[0]->guru->user->nama_lengkap ?? $mappings[0]->guru->nama;
            echo "  ⚠️ Mapel '{$m->nama}' hanya diajar oleh 1 guru: {$gName} (ID: {$mappings[0]->guru_id})\n";
            
            // Cari tahu apakah ada guru lain yang mengajar mapel ini di tingkat lain!
            $otherMappings = GuruMapel::where('mapel_id', $m->id)
                ->where('tingkat_id', '!=', $t->id)
                ->with('guru.user')
                ->get()
                ->pluck('guru.user.nama_lengkap')
                ->unique()
                ->toArray();
            if (!empty($otherMappings)) {
                echo "     💡 Guru lain yang mengajar mapel ini di tingkat berbeda: " . implode(', ', $otherMappings) . "\n";
            }
        }
    }
}
