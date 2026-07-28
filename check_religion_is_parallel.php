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

$demands = json_decode(file_get_contents('storage/demands_job.json'), true);
$blocks = json_decode(file_get_contents('storage/blocks_job.json'), true);
$chromosome = json_decode(file_get_contents('storage/best_chromosome.json'), true);

$slotMap = [];
$jamList = App\Models\JamPelajaran::all();
$hariAktif = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
$sIdx = 0;
foreach ($hariAktif as $hari) {
    $jams = $jamList->where('hari', trim($hari))->sortBy('jam_mulai');
    foreach ($jams as $jam) {
        if ($jam->is_istirahat || $jam->jam_ke == 0) continue;
        $slotMap[$sIdx] = ['hari' => $hari, 'jam_ke' => $jam->jam_ke];
        $sIdx++;
    }
}

// 1. Build busy slots for Seni Budaya teachers
$sbGuruIds = [140, 178, 180, 185]; // From the clash logs
$guruSlots = [];
$kelasSlots = [];
foreach ($blocks as $idx => $block) {
    $dIdx = $block['demand_idx'];
    $demand = $demands[$dIdx];
    $start = $chromosome['slots'][$idx];
    $size = $block['size'];
    $kelasId = $demand['kelas_id'];
    $gMap = $chromosome['teachers'][$dIdx];
    
    for ($i = 0; $i < $size; $i++) {
        $s = $start + $i;
        $kelasSlots[$kelasId][$s][] = $idx;
        foreach ($gMap as $guruId) {
            $guruSlots[$guruId][$s][] = $idx;
        }
    }
}

echo "=== SENI BUDAYA GURU BUSY SLOTS ===\n";
foreach ($sbGuruIds as $gId) {
    $guru = App\Models\Guru::with('user')->find($gId);
    $name = $guru ? ($guru->user->nama_lengkap ?? $guru->nama) : "Guru {$gId}";
    echo "- {$name} (ID: {$gId}) is busy in slots: ";
    $busy = [];
    for ($s = 0; $s < 44; $s++) {
        if (isset($guruSlots[$gId][$s])) {
            $day = $slotMap[$s]['hari'];
            $jam = $slotMap[$s]['jam_ke'];
            $busy[] = "{$day}-J{$jam}";
        }
    }
    echo implode(', ', $busy) . "\n";
}

$targetClasses = ['XI-3', 'XI-5', 'XII-3'];
echo "\n=== TARGET CLASSES EMPTY SLOTS ===\n";
foreach ($targetClasses as $cName) {
    $kelas = App\Models\Kelas::where('nama', $cName)->first();
    if (!$kelas) continue;
    $kId = $kelas->id;
    echo "- Class {$cName} (ID: {$kId}) occupied slots: ";
    $occupied = [];
    $empty = [];
    for ($s = 0; $s < 44; $s++) {
        if (isset($kelasSlots[$kId][$s])) {
            $day = $slotMap[$s]['hari'];
            $jam = $slotMap[$s]['jam_ke'];
            $occupied[] = "{$day}-J{$jam}";
        } else {
            $day = $slotMap[$s]['hari'];
            $jam = $slotMap[$s]['jam_ke'];
            $empty[] = "{$day}-J{$jam}";
        }
    }
    echo implode(', ', $occupied) . "\n";
    echo "  * Empty slots: " . implode(', ', $empty) . "\n";
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
