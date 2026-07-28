<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\GuruMapel;
use App\Models\JamPelajaran;
use App\Models\Mapel;

echo "=== MENGANALISA 10 BENTROK TERAKHIR YANG STAGNANT ===\n";

$jamList = JamPelajaran::all();
$allDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$dbDays = $jamList->pluck('hari')->unique()->toArray();
$hariAktif = array_values(array_intersect($allDays, $dbDays));

$slotMap = [];
$s = 0;
foreach ($hariAktif as $hIdx => $hari) {
    $jamsForHari = $jamList->where('hari', trim($hari))->sortBy('jam_mulai');
    $jIdx = 0;
    foreach ($jamsForHari as $jam) {
        if ($jam->is_istirahat || $jam->jam_ke == 0) continue;
        $slotMap[$s] = [
            'hari' => trim($hari),
            'hari_idx' => $hIdx,
            'jam_ke' => $jam->jam_ke,
            'jam_pos' => $jIdx,
            'jam_pelajaran_id' => $jam->id,
        ];
        $s++;
        $jIdx++;
    }
}
$totalSlots = count($slotMap);

$validBlockStarts = [];
for ($size = 1; $size <= 4; $size++) {
    $validBlockStarts[$size] = [];
    for ($slot = 0; $slot <= $totalSlots - $size; $slot++) {
        if ($slotMap[$slot]['hari_idx'] === $slotMap[$slot + $size - 1]['hari_idx']) {
            $validBlockStarts[$size][] = $slot;
        }
    }
}

$kelasList = Kelas::with(['jurusan', 'tingkat'])->get();
$kurikulumList = Kurikulum::with('mapel')->get();
$guruMapelAll = GuruMapel::all();

$demands = [];
foreach ($kelasList as $k) {
    $kuriList = $kurikulumList->where('tingkat_id', $k->tingkat_id);
    if ($k->jurusan_id) {
        $kuriList = $kuriList->filter(fn($kuri) => is_null($kuri->jurusan_id) || $kuri->jurusan_id == $k->jurusan_id);
    } else {
        $kuriList = $kuriList->whereNull('jurusan_id');
    }

    $kelasDemands = [];
    foreach ($kuriList as $kuri) {
        if (!$kuri->mapel) continue;
        $eligibleGurus = $guruMapelAll->where('mapel_id', $kuri->mapel_id)
            ->where('tingkat_id', $k->tingkat_id)
            ->filter(fn($gm) => is_null($gm->jurusan_id) || $gm->jurusan_id == $k->jurusan_id)
            ->pluck('guru_id')
            ->values()
            ->toArray();

        if (empty($eligibleGurus)) continue;

        if ($kuri->mapel->is_parallel) {
            $kelompok = $kuri->mapel->kelompok_paralel ?: ('id_' . $kuri->mapel->id);
            $key = 'parallel_' . md5($kelompok) . '_' . $kuri->mapel->jam_per_minggu . '_' . $kuri->mapel->jam_per_hari;
            if (!isset($kelasDemands[$key])) {
                $kelasDemands[$key] = [
                    'kelas_id' => $k->id,
                    'mapel_ids' => [],
                    'jam_per_minggu' => $kuri->mapel->jam_per_minggu,
                    'jam_per_hari' => $kuri->mapel->jam_per_hari,
                    'eligible_gurus' => []
                ];
            }
            if (!in_array($kuri->mapel_id, $kelasDemands[$key]['mapel_ids'])) {
                $kelasDemands[$key]['mapel_ids'][] = $kuri->mapel_id;
                $kelasDemands[$key]['eligible_gurus'][$kuri->mapel_id] = array_values($eligibleGurus);
            }
        } else {
            $demands[] = [
                'kelas_id' => $k->id,
                'mapel_ids' => [$kuri->mapel_id],
                'jam_per_minggu' => $kuri->mapel->jam_per_minggu,
                'jam_per_hari' => $kuri->mapel->jam_per_hari,
                'eligible_gurus' => [
                    $kuri->mapel_id => array_values($eligibleGurus)
                ]
            ];
        }
    }
    foreach ($kelasDemands as $kd) {
        $demands[] = $kd;
    }
}

$blocks = [];
foreach ($demands as $dIdx => $demand) {
    $sisa = $demand['jam_per_minggu'];
    $maxPerHari = $demand['jam_per_hari'];
    while ($sisa > 0) {
        $maxPH = (empty($maxPerHari) || $maxPerHari <= 0) ? $sisa : $maxPerHari;
        $take = min($sisa, $maxPH);
        $blocks[] = [
            'demand_idx' => $dIdx,
            'size' => $take
        ];
        $sisa -= $take;
    }
}

$ctx = [
    'demands' => $demands,
    'blocks' => $blocks,
    'slotMap' => $slotMap,
    'validBlockStarts' => $validBlockStarts,
];

function evaluateDetailed($chrom, $ctx) {
    $demands = $ctx['demands'];
    $blocks = $ctx['blocks'];
    $slotMap = $ctx['slotMap'];
    
    $guruSlots = [];
    $kelasSlots = [];
    $guruConflicts = 0;
    $kelasConflicts = 0;
    $clashInfo = [];
    
    foreach ($blocks as $bIdx => $block) {
        $dIdx = $block['demand_idx'];
        $demand = $demands[$dIdx];
        $start = $chrom['slots'][$bIdx];
        $size = $block['size'];
        $guruMap = $chrom['teachers'][$dIdx];
        $kelasId = $demand['kelas_id'];
        
        for ($i = 0; $i < $size; $i++) {
            $sIdx = $start + $i;
            $day = $slotMap[$sIdx]['hari'];
            $jam = $slotMap[$sIdx]['jam_ke'];
            
            foreach ($guruMap as $mId => $guruId) {
                if (isset($guruSlots[$guruId][$sIdx])) {
                    $guruConflicts++;
                    $g = \App\Models\Guru::with('user')->find($guruId);
                    $mapel = \App\Models\Mapel::find($mId);
                    $k = \App\Models\Kelas::find($kelasId);
                    $otherKelasId = $guruSlots[$guruId][$sIdx];
                    $ok = \App\Models\Kelas::find($otherKelasId);
                    $okName = $ok->nama ?? 'Kelas Lain';
                    $clashInfo[] = "🚨 GURU CLASH: Guru '" . ($g->user->nama_lengkap ?? $g->nama) . "' mengajar Mapel '{$mapel->nama}' di Kelas '{$k->nama}' pada {$day} jam ke-{$jam}, tapi guru ini sudah mengajar di kelas '{$okName}'!";
                }
                $guruSlots[$guruId][$sIdx] = $kelasId;
            }
            if (isset($kelasSlots[$kelasId][$sIdx])) {
                $kelasConflicts++;
                $k = \App\Models\Kelas::find($kelasId);
                $mapel = \App\Models\Mapel::find(array_key_first($guruMap));
                $clashInfo[] = "🚨 KELAS CLASH: Kelas '{$k->nama}' dijadwalkan Mapel '" . ($mapel->nama ?? 'NULL') . "' pada {$day} jam ke-{$jam}, tapi kelas ini sudah terpakai di mapel/kegiatan lain!";
            }
            $kelasSlots[$kelasId][$sIdx] = true;
        }
    }
    
    return [
        'guru' => $guruConflicts,
        'kelas' => $kelasConflicts,
        'total' => $guruConflicts + $kelasConflicts,
        'clashInfo' => array_unique($clashInfo)
    ];
}

// ... Simple GA loop with dynamic teachers ...
$pop = [];
$popSize = 30;

function createSmartChromosome($ctx) {
    $demands = $ctx['demands'];
    $blocks = $ctx['blocks'];
    $validBlockStarts = $ctx['validBlockStarts'];
    $assignedGurus = [];
    $guruLoad = [];
    foreach ($demands as $dIdx => $demand) {
        $picked = [];
        foreach ($demand['eligible_gurus'] as $mId => $eligible) {
            $bestGuru = $eligible[0];
            $minLoad = PHP_INT_MAX;
            foreach ($eligible as $gid) {
                $load = $guruLoad[$gid] ?? 0;
                if ($load < $minLoad) {
                    $minLoad = $load;
                    $bestGuru = $gid;
                }
            }
            $picked[$mId] = $bestGuru;
            $guruLoad[$bestGuru] = ($guruLoad[$bestGuru] ?? 0) + $demand['jam_per_minggu'];
        }
        $assignedGurus[$dIdx] = $picked;
    }
    
    $slots = [];
    $usedGuruSlots = [];
    $usedKelasSlots = [];
    foreach ($blocks as $bIdx => $block) {
        $dIdx = $block['demand_idx'];
        $size = $block['size'];
        $validStarts = $validBlockStarts[$size];
        $slots[$bIdx] = $validStarts[array_rand($validStarts)];
    }
    return ['slots' => $slots, 'teachers' => $assignedGurus];
}

for ($i = 0; $i < $popSize; $i++) {
    $pop[] = createSmartChromosome($ctx);
}

// Run GA for 100 generations
echo "Mulai evolusi cepat...\n";
$bestChromosome = null;
$bestScore = PHP_INT_MAX;

for ($gen = 1; $gen <= 80; $gen++) {
    $scores = [];
    foreach ($pop as $idx => $chrom) {
        $eval = evaluateDetailed($chrom, $ctx);
        $scores[$idx] = $eval['total'];
        if ($eval['total'] < $bestScore) {
            $bestScore = $eval['total'];
            $bestChromosome = $chrom;
        }
    }
    asort($scores);
    
    // Reproduction
    $newPop = [];
    $bestIndices = array_keys($scores);
    for ($i = 0; $i < 5; $i++) {
        $newPop[] = $pop[$bestIndices[$i]];
    }
    while (count($newPop) < $popSize) {
        $parent = $pop[$bestIndices[rand(0, 10)]];
        // Mutate slots & teachers
        $child = $parent;
        $bIdx = array_rand($child['slots']);
        $size = $blocks[$bIdx]['size'];
        $valid = $validBlockStarts[$size];
        $child['slots'][$bIdx] = $valid[array_rand($valid)];
        
        $newPop[] = $child;
    }
    $pop = $newPop;
}

$finalEval = evaluateDetailed($bestChromosome, $ctx);
echo "\n=== DETAIL BENTROK PADA KROMOSOM TERBAIK (BENTROK: {$finalEval['total']}) ===\n";
foreach ($finalEval['clashInfo'] as $info) {
    echo "{$info}\n";
}
