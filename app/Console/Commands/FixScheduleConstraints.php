<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\Kurikulum;

class FixScheduleConstraints extends Command
{
    protected $signature = 'app:fix-schedule-constraints';
    protected $description = 'Secara otomatis menambah slot Jam Pelajaran agar jadwal matematis bisa digenerate 0 bentrok';

    public function handle()
    {
        $this->info("🔍 Menganalisa constraint jadwal...");

        $jamList = JamPelajaran::where('is_istirahat', false)->get();
        $dbDays = $jamList->pluck('hari')->unique()->toArray();
        $allDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $hariAktif = array_values(array_intersect($allDays, $dbDays));
        
        if (empty($hariAktif)) {
            $hariAktif = $allDays; // Fallback jika kosong
        }

        $totalSlots = 0;
        foreach ($hariAktif as $hari) {
            $totalSlots += $jamList->where('hari', $hari)->count();
        }

        $kelasList = Kelas::all();
        $kurikulumList = Kurikulum::with('mapel')->get();

        $maxDemand = 0;
        $maxKelasName = '';

        foreach ($kelasList as $k) {
            $kuriList = $kurikulumList->where('tingkat_id', $k->tingkat_id);
            if ($k->jurusan_id) {
                $kuriList = $kuriList->filter(function($kuri) use ($k) {
                    return is_null($kuri->jurusan_id) || $kuri->jurusan_id == $k->jurusan_id;
                });
            } else {
                $kuriList = $kuriList->whereNull('jurusan_id');
            }

            $kelasJam = 0;
            $mapelGurus = [];

            foreach ($kuriList as $kuri) {
                $jam = $kuri->mapel->jam_per_minggu;
                if (!$kuri->mapel->is_parallel) {
                    $kelasJam += $jam;
                } else {
                    $key = 'par_' . md5($kuri->mapel->kelompok_paralel);
                    if (!isset($mapelGurus[$k->id][$key])) {
                        $kelasJam += $jam;
                        $mapelGurus[$k->id][$key] = true;
                    }
                }
            }

            if ($kelasJam > $maxDemand) {
                $maxDemand = $kelasJam;
                $maxKelasName = $k->nama;
            }
        }

        $this->info("📊 Total Slot Aktif saat ini: $totalSlots jam/minggu");
        $this->info("📈 Beban Kelas Terberat ($maxKelasName): $maxDemand jam/minggu");

        if ($maxDemand <= $totalSlots) {
            $this->info("✅ Sistem secara matematis SUDAH MUNGKIN untuk mencapai 0 bentrok. Tidak perlu perbaikan!");
            return;
        }

        $shortage = $maxDemand - $totalSlots;
        $this->warn("⚠️ Sistem kekurangan $shortage slot jam pelajaran/minggu!");

        if (!$this->confirm('Apakah Anda ingin sistem secara otomatis menambahkan slot Jam Pelajaran ekstra (sore hari) untuk menutupi kekurangan ini?', true)) {
            $this->info('Dibatalkan.');
            return;
        }

        $added = 0;
        $dayIndex = 0;
        $totalDays = count($hariAktif);

        while ($added < $shortage) {
            $hari = $hariAktif[$dayIndex];
            
            $lastJam = JamPelajaran::where('hari', $hari)->orderBy('jam_ke', 'desc')->first();
            $jamKe = $lastJam ? $lastJam->jam_ke + 1 : 1;
            
            // Set jam mulai dari jam terakhir (jika ada) atau default
            if ($lastJam && $lastJam->jam_selesai) {
                $jamMulai = $lastJam->jam_selesai;
                $timestamp = strtotime($jamMulai);
                $jamSelesai = date("H:i:s", $timestamp + (45 * 60)); // Asumsi 45 menit
            } else {
                $jamMulai = "15:00:00";
                $jamSelesai = "15:45:00";
            }

            JamPelajaran::create([
                'hari' => $hari,
                'jam_ke' => $jamKe,
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'is_istirahat' => false,
                'nama_kegiatan' => 'Jam Tambahan (Auto Fix)',
                'durasi_menit' => 45,
            ]);

            $added++;
            $dayIndex = ($dayIndex + 1) % $totalDays; // Round robin tiap hari
        }

        $this->info("🎉 Selesai! Berhasil menambahkan $added slot jam pelajaran baru.");
        $this->info("💡 Silahkan coba jalankan Generate Jadwal lagi di web. Pasti tembus!");
    }
}
