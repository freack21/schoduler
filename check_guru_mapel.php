<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\GuruMapel;
use App\Models\Guru;
use App\Models\Mapel;

$kelasList = Kelas::with(['tingkat', 'jurusan'])->get();
$kurikulumList = Kurikulum::with('mapel')->get();
$guruMapelAll = GuruMapel::all();

echo "=== DIAGNOSTIK KELAS & GURU PENGAMPU ===\n";

foreach ($kelasList as $kelas) {
    $kuriList = $kurikulumList->where('tingkat_id', $kelas->tingkat_id);
    if ($kelas->jurusan_id) {
        $kuriList = $kuriList->filter(fn($kuri) => is_null($kuri->jurusan_id) || $kuri->jurusan_id == $kelas->jurusan_id);
    } else {
        $kuriList = $kuriList->whereNull('jurusan_id');
    }
    
    echo "\n🏫 Kelas: {$kelas->nama} (Peminatan: " . ($kelas->jurusan->nama ?? 'Semua') . ")\n";
    
    foreach ($kuriList as $kuri) {
        $eligibleGurus = $guruMapelAll->where('mapel_id', $kuri->mapel_id)
            ->where('tingkat_id', $kelas->tingkat_id)
            ->filter(function($gm) use ($kelas) {
                return is_null($gm->jurusan_id) || $gm->jurusan_id == $kelas->jurusan_id;
            });
            
        $guruNames = [];
        foreach ($eligibleGurus as $eg) {
            $g = Guru::with('user')->find($eg->guru_id);
            if ($g) {
                $guruNames[] = ($g->user->nama_lengkap ?? $g->nama);
            }
        }
        
        if (empty($guruNames)) {
            echo "   🚨 ERROR: Mapel '{$kuri->mapel->nama}' TIDAK MEMILIKI GURU PENGAMPU!\n";
        } else {
            echo "   ✅ Mapel '{$kuri->mapel->nama}' -> Gurus: " . implode(', ', $guruNames) . "\n";
        }
    }
}
