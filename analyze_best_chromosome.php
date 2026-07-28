<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Guru;

$filePath = storage_path('best_chromosome.json');
if (!file_exists($filePath)) {
    die("File best_chromosome.json tidak ditemukan!\n");
}

$chromosome = json_decode(file_get_contents($filePath), true);

// Load Demands and Blocks from saved files
$demandsPath = storage_path('demands_job.json');
$blocksPath = storage_path('blocks_job.json');

if (!file_exists($demandsPath) || !file_exists($blocksPath)) {
    die("File demands_job.json atau blocks_job.json tidak ditemukan!\n");
}

$demands = json_decode(file_get_contents($demandsPath), true);
$blocks = json_decode(file_get_contents($blocksPath), true);

// Precompute slot maps
$jamList = \App\Models\JamPelajaran::all();
$allDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$dbDays = $jamList->pluck('hari')->unique()->toArray();
$hariAktif = array_values(array_intersect($allDays, $dbDays));

$slotMap = [];
$s = 0;
foreach ($hariAktif as $hari) {
    $jamsForHari = $jamList->where('hari', trim($hari))->sortBy('jam_mulai');
    foreach ($jamsForHari as $jam) {
        if ($jam->is_istirahat || $jam->jam_ke == 0) continue;
        $slotMap[$s] = [
            'hari' => trim($hari),
            'jam_ke' => $jam->jam_ke,
            'slot_idx' => $s,
        ];
        $s++;
    }
}

echo "=== GURU LOADS IN BEST CHROMOSOME ===\n";
$guruLoads = [];
foreach ($chromosome['teachers'] as $dIdx => $pickedGurus) {
    $demand = $demands[$dIdx];
    $hours = $demand['jam_per_minggu'];
    foreach ($pickedGurus as $guruId) {
        $guruLoads[$guruId] = ($guruLoads[$guruId] ?? 0) + $hours;
    }
}

arsort($guruLoads);
foreach (array_slice($guruLoads, 0, 15, true) as $guruId => $load) {
    $guru = Guru::with('user')->find($guruId);
    $name = $guru ? ($guru->user->nama_lengkap ?? $guru->nama) : "Guru {$guruId}";
    echo "- {$name} (ID: {$guruId}) | Load: {$load} jam\n";
}

echo "\n=== DETAILED SCHEDULE FOR CLASHING CLASSES ===\n";
$targetClasses = ['XI-2', 'XI-3', 'XI-6', 'XI-8', 'XII-4', 'XII-7'];
foreach ($targetClasses as $cName) {
    $kelas = Kelas::where('nama', $cName)->first();
    if (!$kelas) continue;
    
    echo "\n--- KELAS {$cName} (ID: {$kelas->id}) ---\n";
    $scheduledSlots = [];
    
    foreach ($blocks as $bIdx => $block) {
        $dIdx = $block['demand_idx'];
        $demand = $demands[$dIdx];
        if ($demand['kelas_id'] !== $kelas->id) continue;
        
        $start = $chromosome['slots'][$bIdx];
        $size = $block['size'];
        $teachers = $chromosome['teachers'][$dIdx];
        
        $teacherNames = [];
        foreach ($teachers as $mId => $gId) {
            $guru = Guru::find($gId);
            $teacherNames[] = $guru ? $guru->nama : "G{$gId}";
        }
        
        $mapelNames = [];
        foreach ($demand['mapel_ids'] as $mId) {
            $mapel = Mapel::find($mId);
            $mapelNames[] = $mapel ? $mapel->nama : "M{$mId}";
        }
        
        for ($i = 0; $i < $size; $i++) {
            $sIdx = $start + $i;
            $slot = $slotMap[$sIdx] ?? ['hari' => 'Unknown', 'jam_ke' => $sIdx];
            $scheduledSlots[] = [
                'hari' => $slot['hari'],
                'jam' => $slot['jam_ke'],
                'mapel' => implode(' + ', $mapelNames),
                'gurus' => implode(', ', $teacherNames),
                'size' => $size,
                'block_idx' => $bIdx
            ];
        }
    }
    
    // Sort by day and jam
    usort($scheduledSlots, function($a, $b) use ($allDays) {
        $dayA = array_search($a['hari'], $allDays);
        $dayB = array_search($b['hari'], $allDays);
        if ($dayA !== $dayB) return $dayA <=> $dayB;
        return $a['jam'] <=> $b['jam'];
    });
    
    foreach ($scheduledSlots as $s) {
        echo "  * {$s['hari']} Jam ke-{$s['jam']} | {$s['mapel']} (Guru: {$s['gurus']}) [B{$s['block_idx']} size {$s['size']}]\n";
    }
}
