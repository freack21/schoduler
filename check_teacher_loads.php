<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\GuruMapel;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Kurikulum;

$gurus = Guru::with(['user'])->get();
$guruLoads = [];

echo "DEBUG: Total Gurus count: " . count($gurus) . "\n";

foreach ($gurus as $g) {
    $name = $g->user->nama_lengkap ?? $g->nama;
    
    // Find all classes/subjects taught by this teacher
    $mappings = GuruMapel::where('guru_id', $g->id)->get();
    if (count($mappings) > 0) {
        echo "DEBUG: Guru '{$name}' has " . count($mappings) . " mappings\n";
    }
    
    $totalHours = 0;
    $classesCount = 0;
    $classDetails = [];
    
    foreach ($mappings as $m) {
        // Find kurikulum hours for this mapel in classes of this tingkat/jurusan
        $kelasQuery = Kelas::where('tingkat_id', $m->tingkat_id);
        if ($m->jurusan_id) {
            $kelasQuery->where('jurusan_id', $m->jurusan_id);
        }
        
        $kelas = $kelasQuery->get();
        foreach ($kelas as $k) {
            $kur = Kurikulum::where('tingkat_id', $k->tingkat_id)
                ->where('mapel_id', $m->mapel_id)
                ->where(function($q) use ($k) {
                    $q->where('jurusan_id', $k->jurusan_id)
                      ->orWhereNull('jurusan_id');
                })
                ->first();
                
            if ($kur) {
                $totalHours += $kur->jam_per_minggu;
                $classesCount++;
                $classDetails[] = "{$k->nama} ({$kur->mapel->nama}: {$kur->jam_per_minggu} jam)";
            }
        }
    }
    
    if ($totalHours > 0) {
        $guruLoads[] = [
            'id' => $g->id,
            'name' => $name,
            'hours' => $totalHours,
            'classes' => $classesCount,
            'details' => $classDetails
        ];
    }
}

// Sort by hours descending
usort($guruLoads, fn($a, $b) => $b['hours'] <=> $a['hours']);

echo "=== BEBAN MENGAJAR GURU (TOP 20) ===\n";
foreach (array_slice($guruLoads, 0, 20) as $load) {
    echo "- Guru: {$load['name']} (ID: {$load['id']}) | Total: {$load['hours']} jam | {$load['classes']} kelas\n";
    echo "  Detail: " . implode(", ", array_slice($load['details'], 0, 5)) . (count($load['details']) > 5 ? " ... " : "") . "\n";
}
