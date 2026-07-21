<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Kurikulum;
use App\Models\Tingkat;
use Illuminate\Support\Facades\DB;

class FixPeminatanXI extends Command
{
    protected $signature = 'app:fix-peminatan-xi';
    protected $description = 'Membuat pengelompokan peminatan (MIPA/IPS) untuk Kelas XI agar beban belajar tidak 62 jam/minggu';

    public function handle()
    {
        $this->info("🔍 Memulai proses perbaikan peminatan Kelas XI...");

        $tingkatXI = Tingkat::where('nama', 'XI')->first();
        if (!$tingkatXI) {
            $this->error("Tingkat XI tidak ditemukan!");
            return;
        }

        DB::beginTransaction();
        try {
            // 1. Buat "Jurusan" sebagai Peminatan
            $mipa = Jurusan::firstOrCreate(['nama' => 'MIPA'], ['kode' => 'MIPA']);
            $ips = Jurusan::firstOrCreate(['nama' => 'IPS'], ['kode' => 'IPS']);

            $this->line("✅ Membuat pengelompokan Peminatan MIPA dan IPS.");

            // 2. Bagi Kelas XI ke MIPA dan IPS (Separuh MIPA, Separuh IPS)
            $kelasXI = Kelas::where('tingkat_id', $tingkatXI->id)->orderBy('nama')->get();
            $half = ceil($kelasXI->count() / 2);
            
            $mipaCount = 0;
            $ipsCount = 0;

            foreach ($kelasXI->values() as $index => $kelas) {
                if ($index < $half) {
                    $kelas->jurusan_id = $mipa->id;
                    $kelas->save();
                    $mipaCount++;
                } else {
                    $kelas->jurusan_id = $ips->id;
                    $kelas->save();
                    $ipsCount++;
                }
            }
            $this->line("✅ Mengatur $mipaCount Kelas ke Peminatan MIPA, dan $ipsCount Kelas ke Peminatan IPS.");

            // 3. Update Mapping Kurikulum
            $mapelMipa = ['Fisika Lanjutan', 'Kimia Lanjutan', 'Biologi Lanjutan', 'Matematika Lanjut'];
            $mapelIps = ['Ekonomi Lanjutan', 'Sosiologi Lanjutan', 'Geografi Lanjutan', 'Sejarah Peminatan Lanjutan'];

            // Map MIPA
            foreach ($mapelMipa as $mName) {
                $m = Mapel::where('nama', $mName)->first();
                if ($m) {
                    Kurikulum::where('mapel_id', $m->id)
                        ->where('tingkat_id', $tingkatXI->id)
                        ->update(['jurusan_id' => $mipa->id]);
                }
            }

            // Map IPS
            foreach ($mapelIps as $mName) {
                $m = Mapel::where('nama', $mName)->first();
                if ($m) {
                    Kurikulum::where('mapel_id', $m->id)
                        ->where('tingkat_id', $tingkatXI->id)
                        ->update(['jurusan_id' => $ips->id]);
                }
            }

            $this->line("✅ Mapping mata pelajaran Peminatan ke kelompoknya masing-masing berhasil.");

            DB::commit();
            $this->newLine();
            $this->info("🎉 Proses perbaikan selesai! Beban belajar Kelas XI sekarang dipecah dan kembali normal (sekitar 46 jam/minggu).");
            $this->info("💡 Silakan jalankan 'php artisan app:reset-jadwal' lalu Generate Ulang!");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Gagal: " . $e->getMessage());
        }
    }
}
