<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Mapel;

use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\GuruMapel;
use App\Models\Guru;

$kelasList = Kelas::all();
$kurikulumList = Kurikulum::with('mapel')->get();
$guruMapelAll = GuruMapel::all();

echo "=== DIAGNOSTICS: SUBJECT DEMAND VS ELIGIBLE TEACHERS ===\n";

$analyzed = [];

$kristenMapel = App\Models\Mapel::where('nama', 'like', '%Kristen%')->first();
if ($kristenMapel) {
    $totalHours = App\Models\Kurikulum::where('mapel_id', $kristenMapel->id)->get()->sum(function($k) {
        return $k->mapel->jam_per_minggu;
    });
    echo "Total Kristen hours needed: {$totalHours} jam/minggu\n";
    
    $gurus = App\Models\GuruMapel::where('mapel_id', $kristenMapel->id)->get();
    echo "Kristen teachers:\n";
    foreach ($gurus as $g) {
        echo "- {$g->guru->nama} (ID: {$g->guru_id})\n";
    }
}
exit;

foreach ($kelasList as $kelas) {
    $kuriList = $kurikulumList->where('tingkat_id', $kelas->tingkat_id);
    if ($kelas->jurusan_id) {
        $kuriList = $kuriList->filter(fn($kuri) => is_null($kuri->jurusan_id) || $kuri->jurusan_id == $kelas->jurusan_id);
    } else {
        $kuriList = $kuriList->whereNull('jurusan_id');
    }
    
    foreach ($kuriList as $kuri) {
        $mapel = $kuri->mapel;
        $key = "{$kelas->tingkat_id}_{$kelas->jurusan_id}_{$mapel->id}";
        
        if (!isset($analyzed[$key])) {
            $analyzed[$key] = [
                'tingkat_id' => $kelas->tingkat_id,
                'jurusan_id' => $kelas->jurusan_id,
                'mapel_id' => $mapel->id,
                'mapel_nama' => $mapel->nama,
                'jam_per_minggu' => $mapel->jam_per_minggu,
                'kelas_count' => 0,
                'classes' => [],
                'eligible_gurus' => []
            ];
            
            // Find eligible teachers
            $eligible = $guruMapelAll->where('mapel_id', $mapel->id)
                ->where('tingkat_id', $kelas->tingkat_id)
                ->filter(fn($gm) => is_null($gm->jurusan_id) || $gm->jurusan_id == $kelas->jurusan_id)
                ->pluck('guru_id')
                ->unique()
                ->toArray();
                
            $teacherNames = [];
            foreach ($eligible as $gid) {
                $guru = Guru::with('user')->find($gid);
                $teacherNames[] = $guru ? ($guru->user->nama_lengkap ?? $guru->nama) : "G{$gid}";
            }
            $analyzed[$key]['eligible_gurus'] = $teacherNames;
        }
        
        $analyzed[$key]['kelas_count']++;
        $analyzed[$key]['classes'][] = $kelas->nama;
    }
}

// Print results ordered by demand / teachers ratio (bottleneck index)
usort($analyzed, function($a, $b) {
    $demandA = $a['kelas_count'] * $a['jam_per_minggu'];
    $teachersA = count($a['eligible_gurus']) ?: 1;
    $ratioA = $demandA / $teachersA;
    
    $demandB = $b['kelas_count'] * $b['jam_per_minggu'];
    $teachersB = count($b['eligible_gurus']) ?: 1;
    $ratioB = $demandB / $teachersB;
    
    return $ratioB <=> $ratioA; // Descending
});

foreach ($analyzed as $a) {
    $demand = $a['kelas_count'] * $a['jam_per_minggu'];
    $tCount = count($a['eligible_gurus']);
    $ratio = $tCount > 0 ? round($demand / $tCount, 1) : 999;
    
    echo "Mapel: {$a['mapel_nama']} | Tingkat: {$a['tingkat_id']} | Jurusan: " . ($a['jurusan_id'] ?: 'NULL') . "\n";
    echo "  - Classes: " . implode(', ', $a['classes']) . " ({$a['kelas_count']} classes)\n";
    echo "  - Hours/class: {$a['jam_per_minggu']} | Total Demand: {$demand} hours\n";
    echo "  - Eligible Teachers ({$tCount}): " . implode(', ', $a['eligible_gurus']) . "\n";
    echo "  - Bottleneck Ratio (Hours/Teacher): {$ratio} hours/teacher\n\n";
}
exit;
