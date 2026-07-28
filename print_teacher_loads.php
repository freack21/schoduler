<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Guru;
use App\Models\GuruMapel;
use App\Models\Kurikulum;
use App\Models\Kelas;

$gurus = Guru::with('user')->get();
$kelasList = Kelas::all();
$kurikulumList = Kurikulum::with('mapel')->get();
$guruMapelAll = GuruMapel::all();

$loads = [];
foreach ($kelasList as $k) {
    $kuriList = $kurikulumList->where('tingkat_id', $k->tingkat_id);
    if ($k->jurusan_id) {
        $kuriList = $kuriList->filter(fn($kuri) => is_null($kuri->jurusan_id) || $kuri->jurusan_id == $k->jurusan_id);
    } else {
        $kuriList = $kuriList->whereNull('jurusan_id');
    }

    foreach ($kuriList as $kuri) {
        if (!$kuri->mapel) continue;
        
        $eligible = $guruMapelAll->where('mapel_id', $kuri->mapel_id)
            ->where('tingkat_id', $k->tingkat_id)
            ->filter(fn($gm) => is_null($gm->jurusan_id) || $gm->jurusan_id == $k->jurusan_id)
            ->pluck('guru_id')
            ->toArray();
            
        if (empty($eligible)) continue;
        
        // If only 1 eligible teacher, they must take it
        if (count($eligible) === 1) {
            $gid = $eligible[0];
            $loads[$gid] = ($loads[$gid] ?? 0) + $kuri->mapel->jam_per_minggu;
        }
    }
}

echo "=== GURU DENGAN BEBAN TETAP (HANYA 1 PILIHAN) ===\n";
foreach ($gurus as $g) {
    $gName = $g->user->nama_lengkap ?? $g->nama;
    $load = $loads[$g->id] ?? 0;
    if ($load > 0) {
        echo "- Guru: {$gName} (ID: {$g->id}) | Beban Tetap: {$load} jam/minggu\n";
    }
}
