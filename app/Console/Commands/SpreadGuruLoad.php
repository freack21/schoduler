<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Guru;
use App\Models\GuruMapel;
use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\Kurikulum;

class SpreadGuruLoad extends Command
{
    protected $signature = 'app:spread-guru-load';
    protected $description = 'Sebar beban guru yang overload ke guru yang nganggur secara konsisten (1 mapel/guru)';

    public function handle()
    {
        $this->info("🔍 Menganalisa beban mengajar guru...");

        // 1. Hitung total slot sistem
        $jamList = JamPelajaran::where('is_istirahat', false)->get();
        $dbDays = $jamList->pluck('hari')->unique()->toArray();
        $allDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $hariAktif = array_values(array_intersect($allDays, $dbDays));
        if (empty($hariAktif)) $hariAktif = $allDays;

        $totalSlots = 0;
        foreach ($hariAktif as $hari) {
            $totalSlots += $jamList->where('hari', $hari)->count();
        }

        // Max beban aman diturunkan ke 38 jam agar load tersebar ke banyak guru
        $safeThreshold = 38; 

        // 2. Ambil semua guru idle
        $activeGuruIds = GuruMapel::pluck('guru_id')->unique()->toArray();
        $idleGurus = Guru::with('user')->whereNotIn('id', $activeGuruIds)->get()->values();

        if ($idleGurus->isEmpty()) {
            $this->warn("⚠️ Tidak ada guru nganggur (idle) yang tersedia untuk menampung limpahan jam.");
            return;
        }

        $this->info("👥 Ditemukan {$idleGurus->count()} guru nganggur.");
        
        $idleIndex = 0;
        $iteration = 1;
        $changesMade = false;

        while (true) {
            // Recalculate load
            $kelasList = Kelas::all();
            $kurikulumList = Kurikulum::with('mapel')->get();
            $guruMapelAll = GuruMapel::all();

            $guruTotalLoad = [];
            $guruMapelLoad = [];

            foreach ($kelasList as $k) {
                $kuriList = $kurikulumList->where('tingkat_id', $k->tingkat_id);
                if ($k->jurusan_id) {
                    $kuriList = $kuriList->filter(function($kuri) use ($k) {
                        return is_null($kuri->jurusan_id) || $kuri->jurusan_id == $k->jurusan_id;
                    });
                } else {
                    $kuriList = $kuriList->whereNull('jurusan_id');
                }

                foreach ($kuriList as $kuri) {
                    $eligibleGurus = $guruMapelAll->where('mapel_id', $kuri->mapel_id)
                        ->where('tingkat_id', $k->tingkat_id)
                        ->filter(function($gm) use ($k) {
                            return is_null($gm->jurusan_id) || $gm->jurusan_id == $k->jurusan_id;
                        })
                        ->pluck('guru_id')
                        ->values()
                        ->toArray();
                    
                    $jam = $kuri->mapel->jam_per_minggu;
                    if (!empty($eligibleGurus)) {
                        $loadPerGuru = $jam / count($eligibleGurus);
                        foreach ($eligibleGurus as $gid) {
                            $guruTotalLoad[$gid] = ($guruTotalLoad[$gid] ?? 0) + $loadPerGuru;
                            $guruMapelLoad[$gid][$kuri->mapel_id] = ($guruMapelLoad[$gid][$kuri->mapel_id] ?? 0) + $loadPerGuru;
                        }
                    }
                }
            }

            // Find worst overloaded guru
            arsort($guruTotalLoad);
            
            $worstGuruId = null;
            $worstLoad = 0;
            foreach ($guruTotalLoad as $gid => $load) {
                if ($load > $safeThreshold) {
                    $worstGuruId = $gid;
                    $worstLoad = $load;
                    break; // Ambil yang paling atas (paling overload)
                }
            }

            if (!$worstGuruId) {
                $this->info("✅ Semua beban guru sudah di bawah batas aman ({$safeThreshold} jam/minggu).");
                break;
            }

            if ($idleIndex >= $idleGurus->count()) {
                $this->warn("⚠️ Guru nganggur habis! Masih ada guru yang overload tapi tidak ada lagi yang bisa menampung.");
                break;
            }

            // Temukan mapel penyumbang beban terbesar untuk guru ini
            $mapelLoads = $guruMapelLoad[$worstGuruId] ?? [];
            arsort($mapelLoads);
            $heaviestMapelId = array_key_first($mapelLoads);
            $heaviestLoadAmount = $mapelLoads[$heaviestMapelId];

            $gAsli = Guru::with('user')->find($worstGuruId);
            $namaAsli = $gAsli ? $gAsli->user->nama_lengkap : "Guru $worstGuruId";
            
            $idleGuru = $idleGurus[$idleIndex];
            $namaIdle = $idleGuru->user->nama_lengkap ?? "Guru $idleGuru->id";
            
            $this->info("🔄 Iterasi $iteration: Guru '$namaAsli' overload (~" . round($worstLoad, 1) . " jam). Melimpahkan Mapel ID $heaviestMapelId ke Guru Nganggur '$namaIdle'...");

            // Duplikasi semua assignment GuruMapel dari worstGuru untuk heaviestMapelId ke idleGuru
            $rowsToCopy = GuruMapel::where('guru_id', $worstGuruId)
                                   ->where('mapel_id', $heaviestMapelId)
                                   ->get();

            foreach ($rowsToCopy as $row) {
                // Pastikan tidak ada duplikat
                GuruMapel::firstOrCreate([
                    'guru_id' => $idleGuru->id,
                    'mapel_id' => $row->mapel_id,
                    'tingkat_id' => $row->tingkat_id,
                    'jurusan_id' => $row->jurusan_id,
                ]);
            }

            $idleIndex++;
            $iteration++;
            $changesMade = true;
        }

        if ($changesMade) {
            $this->info("🎉 Selesai! Berhasil menyebar beban jadwal ke guru nganggur.");
            $this->info("💡 Silahkan jalankan Generate Jadwal lagi di web!");
        } else {
            $this->info("👍 Tidak ada perubahan yang diperlukan.");
        }
    }
}
