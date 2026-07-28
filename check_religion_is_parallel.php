<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Mapel;

$filePath = storage_path('best_chromosome.json');
$demands = json_decode(file_get_contents(storage_path('demands_job.json')), true);
$blocks = json_decode(file_get_contents(storage_path('blocks_job.json')), true);
$chromosome = json_decode(file_get_contents($filePath), true);

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

$lidyaId = 191; // Lidya Febrianti
echo "=== LIDYA FEBRIANTI SCHEDULE ON FRIDAY ===\n";
foreach ($blocks as $bIdx => $block) {
    $dIdx = $block['demand_idx'];
    $demand = $demands[$dIdx];
    $teachers = $chromosome['teachers'][$dIdx] ?? [];
    
    if (in_array($lidyaId, $teachers)) {
        $start = $chromosome['slots'][$bIdx];
        $size = $block['size'];
        $kelas = \App\Models\Kelas::find($demand['kelas_id']);
        
        for ($i = 0; $i < $size; $i++) {
            $sIdx = $start + $i;
            $slot = $slotMap[$sIdx] ?? null;
            if ($slot && $slot['hari'] === 'Jumat') {
                echo "- Jumat Jam ke-{$slot['jam_ke']} | Kelas: {$kelas->nama} (Block: B{$bIdx})\n";
            }
        }
    }
}
exit;
