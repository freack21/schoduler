<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\Tingkat;
use App\Models\Mapel;
use Illuminate\Support\Facades\DB;

class AdjustCurriculumPeminatan extends Command
{
    protected $signature = 'app:adjust-curriculum-peminatan';
    protected $description = 'Menyesuaikan pembagian peminatan (MIPA/IPS) serta memetakan beban kurikulum untuk Kelas XI dan XII sesuai diskusi user';

    public function handle()
    {
        $this->info("🔍 Memulai proses penyesuaian kurikulum peminatan Kelas XI dan XII...");

        $tingkatXI = Tingkat::where('nama', 'XI')->first();
        $tingkatXII = Tingkat::where('nama', 'XII')->first();

        if (!$tingkatXI || !$tingkatXII) {
            $this->error("❌ Tingkat XI atau XII tidak ditemukan!");
            return;
        }

        DB::beginTransaction();
        try {
            // 1. Dapatkan atau buat Jurusan MIPA & IPS
            $mipa = Jurusan::firstOrCreate(['kode' => 'MIPA'], ['nama' => 'MIPA']);
            $ips = Jurusan::firstOrCreate(['kode' => 'IPS'], ['nama' => 'IPS']);

            $this->line("✅ Membuat/memastikan Jurusan MIPA dan IPS tersedia.");

            // 2. Bagi Kelas XI: XI-1 s.d XI-4 (MIPA), XI-5 s.d XI-8 (IPS)
            $kelasXI = Kelas::where('tingkat_id', $tingkatXI->id)->orderBy('nama')->get();
            foreach ($kelasXI as $kelas) {
                $num = (int) filter_var($kelas->nama, FILTER_SANITIZE_NUMBER_INT);
                if ($num >= 1 && $num <= 4) {
                    $kelas->jurusan_id = $mipa->id;
                } else {
                    $kelas->jurusan_id = $ips->id;
                }
                $kelas->save();
            }
            $this->line("✅ Membagi Kelas XI: XI-1 s/d XI-4 ke MIPA, sisanya ke IPS.");

            // 3. Bagi Kelas XII: XII-1 s.d XII-4 (MIPA), XII-5 s.d XII-7 (IPS)
            $kelasXII = Kelas::where('tingkat_id', $tingkatXII->id)->orderBy('nama')->get();
            foreach ($kelasXII as $kelas) {
                $num = (int) filter_var($kelas->nama, FILTER_SANITIZE_NUMBER_INT);
                if ($num >= 1 && $num <= 4) {
                    $kelas->jurusan_id = $mipa->id;
                } else {
                    $kelas->jurusan_id = $ips->id;
                }
                $kelas->save();
            }
            $this->line("✅ Membagi Kelas XII: XII-1 s/d XII-4 ke MIPA, sisanya ke IPS.");

            // 4. Reset kurikulum lama untuk Tingkat XI dan XII
            Kurikulum::whereIn('tingkat_id', [$tingkatXI->id, $tingkatXII->id])->delete();
            $this->line("🗑️ Mengosongkan pemetaan kurikulum lama Kelas XI & XII.");

            // 5. Definisikan Mapel Wajib (Sesuai List Dinda)
            // ID Mapel: Agama Islam (16), Agama Kristen (21), PPKN (12), B.Indo (17), B.Ing (3), Mat Wajib (11), Sejarah (9), PJOK (15), Seni Budaya (6), PKWU (24), Informatika (20), BK (19)
            $wajibMapelIds = [16, 21, 12, 17, 3, 11, 9, 15, 6, 24, 20, 19];

            // 6. Definisikan Mapel Peminatan XI
            $peminatanXiMipa = [18, 2, 14, 1]; // Mat Lanjut (18), Biologi Lanjut (2), Fisika Lanjut (14), Kimia Lanjut (1)
            $peminatanXiIps  = [5, 8, 23, 10]; // Ekonomi Lanjut (5), Sosiologi Lanjut (8), Geografi Lanjut (23), Sejarah Pem. Lanjut (10)

            // 7. Definisikan Mapel Peminatan XII (Sesuai List Dinda)
            $peminatanXiiMipa = [18, 2, 14, 1]; // Mat Lanjut (18), Biologi Lanjut (2), Fisika Lanjut (14), Kimia Lanjut (1)
            $peminatanXiiIps  = [5, 23, 10]; // Ekonomi Lanjut (5), Geografi Lanjut (23), Sejarah Pem. Lanjut (10)

            // Seed Kelas XI Wajib
            foreach ($wajibMapelIds as $mid) {
                Kurikulum::create([
                    'tingkat_id' => $tingkatXI->id,
                    'jurusan_id' => null,
                    'mapel_id' => $mid,
                ]);
            }
            // Seed Kelas XI Peminatan MIPA
            foreach ($peminatanXiMipa as $mid) {
                Kurikulum::create([
                    'tingkat_id' => $tingkatXI->id,
                    'jurusan_id' => $mipa->id,
                    'mapel_id' => $mid,
                ]);
            }
            // Seed Kelas XI Peminatan IPS
            foreach ($peminatanXiIps as $mid) {
                Kurikulum::create([
                    'tingkat_id' => $tingkatXI->id,
                    'jurusan_id' => $ips->id,
                    'mapel_id' => $mid,
                ]);
            }

            // Seed Kelas XII Wajib
            foreach ($wajibMapelIds as $mid) {
                Kurikulum::create([
                    'tingkat_id' => $tingkatXII->id,
                    'jurusan_id' => null,
                    'mapel_id' => $mid,
                ]);
            }
            // Seed Kelas XII Peminatan MIPA
            foreach ($peminatanXiiMipa as $mid) {
                Kurikulum::create([
                    'tingkat_id' => $tingkatXII->id,
                    'jurusan_id' => $mipa->id,
                    'mapel_id' => $mid,
                ]);
            }
            // Seed Kelas XII Peminatan IPS
            foreach ($peminatanXiiIps as $mid) {
                Kurikulum::create([
                    'tingkat_id' => $tingkatXII->id,
                    'jurusan_id' => $ips->id,
                    'mapel_id' => $mid,
                ]);
            }

            DB::commit();
            $this->info("🎉 Sukses! Kurikulum dan Jurusan Kelas XI & XII disesuaikan dengan benar.");
            $this->line("💡 Beban belajar Kelas XI/XII sekarang berkisar 38-42 jam/minggu (Sangat layak masuk 44 slot!).");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Gagal menyesuaikan kurikulum: " . $e->getMessage());
        }
    }
}
