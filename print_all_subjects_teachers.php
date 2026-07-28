<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Mapel;
use App\Models\GuruMapel;
use App\Models\Tingkat;
use App\Models\Kurikulum;

$tingkats = Tingkat::whereIn('nama', ['XI', 'XII'])->get();

echo "=== BEBAN DAN GURU MAPEL UNTUK SETIAP KURIKULUM XI & XII ===\n";

foreach ($tingkats as $t) {
    echo "\n----------------------------------------\n";
    echo "TINGKAT: {$t->nama}\n";
    echo "----------------------------------------\n";
    
    // Ambil kurikulum untuk tingkat ini
    $kuriList = Kurikulum::where('tingkat_id', $t->id)->with('mapel')->get();
    
    foreach ($kuriList as $kuri) {
        if (!$kuri->mapel) continue;
        $m = $kuri->mapel;
        
        $mappings = GuruMapel::where('mapel_id', $m->id)
            ->where('tingkat_id', $t->id)
            ->with('guru.user')
            ->get();
            
        $teachers = [];
        foreach ($mappings as $map) {
            $teachers[] = ($map->guru->user->nama_lengkap ?? $map->guru->nama) . " (ID: {$map->guru_id})";
        }
        
        $jurusanName = $kuri->jurusan_id ? \App\Models\Jurusan::find($kuri->jurusan_id)->nama : 'Semua';
        
        echo "- Mapel: {$m->nama} | Jurusan: {$jurusanName} | Jam: {$m->jam_per_minggu} jam\n";
        echo "  Mapped Teachers (" . count($teachers) . "): " . implode(', ', $teachers) . "\n";
    }
}
