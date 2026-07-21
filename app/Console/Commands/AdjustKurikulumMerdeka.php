<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Mapel;
use App\Models\Kurikulum;
use App\Models\Tingkat;
use Illuminate\Support\Facades\DB;

class AdjustKurikulumMerdeka extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:adjust-kurikulum-merdeka';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menyesuaikan beban kurikulum merdeka (memindahkan mapel lanjutan ke kelas 11 dan menghapusnya dari kelas 12)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔍 Memulai proses penyesuaian kurikulum merdeka...");

        $tingkatXI = Tingkat::where('nama', 'XI')->first();
        $tingkatXII = Tingkat::where('nama', 'XII')->first();

        if (!$tingkatXI || !$tingkatXII) {
            $this->error("❌ Data Tingkat XI atau XII tidak ditemukan di database!");
            return;
        }

        DB::beginTransaction();
        try {
            // 1. Pindahkan Mapel yang seharusnya ada di XI tapi malah nyasar di XII
            $moveMapels = ['Fisika Lanjutan', 'Kimia Lanjutan', 'Geografi Lanjutan'];
            $movedCount = 0;
            
            foreach ($moveMapels as $mName) {
                $m = Mapel::where('nama', $mName)->first();
                if ($m) {
                    $updated = Kurikulum::where('mapel_id', $m->id)
                        ->where('tingkat_id', $tingkatXII->id)
                        ->update(['tingkat_id' => $tingkatXI->id]);
                    
                    if ($updated > 0) {
                        $this->line("✅ Memindahkan [$mName] dari Kelas XII ke Kelas XI");
                        $movedCount++;
                    }
                }
            }

            // 2. Hapus Mapel Lanjutan dari Kelas XII agar bebannya berkurang
            $deleteMapels = ['Biologi Lanjutan', 'Ekonomi Lanjutan', 'Sejarah Peminatan Lanjutan', 'Matematika Lanjut'];
            $deletedCount = 0;
            
            foreach ($deleteMapels as $mName) {
                $m = Mapel::where('nama', $mName)->first();
                if ($m) {
                    $deleted = Kurikulum::where('mapel_id', $m->id)
                        ->where('tingkat_id', $tingkatXII->id)
                        ->delete();
                    
                    if ($deleted > 0) {
                        $this->line("🗑️ Menghapus [$mName] dari Kelas XII");
                        $deletedCount++;
                    }
                }
            }

            DB::commit();
            $this->newLine();
            $this->info("🎉 Penyesuaian kurikulum merdeka selesai!");
            $this->info("📊 Total dipindahkan: $movedCount mapel");
            $this->info("📊 Total dihapus dari XII: $deletedCount mapel");
            $this->line("💡 Kelas XII sekarang fokus pada mapel dasar dan beban mengajar sudah ringan (max ~30 jam/minggu).");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Terjadi kesalahan: " . $e->getMessage());
        }
    }
}
