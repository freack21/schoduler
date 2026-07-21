<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Jadwal;
use App\Models\ScheduleGeneration;
use Illuminate\Support\Facades\DB;

class ResetJadwal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-jadwal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menghapus seluruh hasil generate jadwal dan riwayat pemrosesannya tanpa menyentuh data master';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warn("⚠️ PERINGATAN: Perintah ini akan menghapus SELURUH jadwal yang sudah ada!");
        
        if (!$this->confirm('Apakah Anda yakin ingin menghapus semua jadwal dan riwayat generate?', true)) {
            $this->info("Operasi dibatalkan.");
            return;
        }

        $this->info("🗑️ Menghapus data jadwal dan riwayat generate...");

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // Hapus isi tabel jadwal
            $deletedJadwal = Jadwal::query()->delete();
            $this->line("✅ Menghapus $deletedJadwal record dari tabel jadwal.");

            // Hapus isi tabel riwayat generate
            $deletedHistory = ScheduleGeneration::query()->delete();
            $this->line("✅ Menghapus $deletedHistory record dari tabel riwayat generate (schedule_generations).");

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->newLine();
            $this->info("🎉 Proses reset selesai! Ruang penjadwalan sekarang bersih total.");
            $this->info("💡 Data master (Guru, Kelas, Mapel, Kurikulum, Jam Pelajaran) TIDAK tersentuh.");

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->error("❌ Terjadi kesalahan: " . $e->getMessage());
        }
    }
}
