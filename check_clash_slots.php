<?php
// Analisa slot tersedia untuk kelas yang clash
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\Jadwal;
use App\Models\Kurikulum;
use App\Models\GuruMapel;
use App\Models\Pengaturan;

$tahun = Pengaturan::activeTahunAjaran();

// Cari kelas XI-5 dan XII-3
$kelasTargets = ['XI-5', 'XII-3'];
$mapelTargets = [
    'XI-5'  => ['Bahasa Indonesia', 'Matematika Wajib'],
    'XII-3' => ['Biologi Lanjutan', 'PJOK'],
];

foreach ($kelasTargets as $namaKelas) {
    $kelas = Kelas::where('nama', $namaKelas)->first();
    if (!$kelas) { echo "Kelas $namaKelas tidak ditemukan!\n"; continue; }

    echo "\n=== $namaKelas (ID: {$kelas->id}) ===\n";
    
    // Jadwal yang sudah ada
    $jadwal = Jadwal::where('kelas_id', $kelas->id)
        ->where('tahun_ajaran', $tahun)
        ->with(['mapel', 'jamPelajaran', 'guru'])
        ->get();
    
    echo "Jadwal saat ini (" . count($jadwal) . " slot):\n";
    $usedSlots = [];
    foreach ($jadwal as $j) {
        $key = $j->hari . ' jam ' . $j->jamPelajaran->jam_ke;
        $guruNama = $j->guru ? $j->guru->nama_guru : 'N/A';
        echo "  {$j->hari} jam {$j->jamPelajaran->jam_ke}: {$j->mapel->nama_mapel} ({$guruNama})\n";
        $usedSlots[$key] = true;
    }
    
    // Slot total yang ada
    $allSlots = JamPelajaran::orderBy('hari')->orderBy('jam_ke')->get();
    echo "\nSlot KOSONG untuk $namaKelas:\n";
    foreach ($allSlots as $slot) {
        $key = $slot->hari . ' jam ' . $slot->jam_ke;
        if (!isset($usedSlots[$key])) {
            echo "  {$slot->hari} jam {$slot->jam_ke} (ID: {$slot->id})\n";
        }
    }
    
    // Cek mapel yang clash
    foreach ($mapelTargets[$namaKelas] as $namaMapel) {
        echo "\nMapel '$namaMapel' di kurikulum $namaKelas:\n";
        $kurikulums = Kurikulum::whereHas('mapel', function($q) use ($namaMapel) { $q->where('nama_mapel', $namaMapel); })
            ->where('kelas_id', $kelas->id)
            ->with('mapel')
            ->get();
        foreach ($kurikulums as $k) {
            echo "  ID: {$k->id}, jam_per_minggu: {$k->jam_per_minggu}\n";
            // Guru yang bisa mengajar
            $gurus = GuruMapel::where('mapel_id', $k->mapel_id)->with('guru')->get();
            echo "  Guru eligible: " . $gurus->pluck('guru.nama_guru')->implode(', ') . "\n";
        }
    }
}
