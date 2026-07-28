<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JamPelajaran;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SeedJamPelajaranKurmer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-jam-pelajaran-kurmer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate ulang referensi jam pelajaran standar Kurikulum Merdeka (Senin-Jumat, max jam 15:45)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🗑️ Menghapus data jam pelajaran lama...");
        
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            JamPelajaran::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->info("⚙️ Membuat jadwal Kurikulum Merdeka (5 Hari Sekolah)...");

            $jadwal = [];

            // ─── SENIN ───
            $waktuSenin = [
                ['07:00', '07:45', true, 'Upacara Bendera', 45],
                ['07:45', '08:30', false, null, 45],
                ['08:30', '09:15', false, null, 45],
                ['09:15', '10:00', false, null, 45],
                ['10:00', '10:20', true, 'Istirahat 1', 20],
                ['10:20', '11:05', false, null, 45],
                ['11:05', '11:50', false, null, 45],
                ['11:50', '12:30', false, null, 40],
                ['12:30', '13:20', true, 'Istirahat 2 (Ishoma)', 50],
                ['13:20', '14:00', false, null, 40],
                ['14:00', '14:40', false, null, 40],
                ['14:40', '15:20', false, null, 40],
            ];
            $this->buildDay('Senin', $waktuSenin, $jadwal);

            // ─── SELASA, RABU, KAMIS ───
            $waktuSelasaKamis = [
                ['07:00', '07:15', true, 'Literasi / Kultum Pagi', 15],
                ['07:15', '08:00', false, null, 45],
                ['08:00', '08:45', false, null, 45],
                ['08:45', '09:30', false, null, 45],
                ['09:30', '10:15', false, null, 45],
                ['10:15', '10:30', true, 'Istirahat 1', 15],
                ['10:30', '11:15', false, null, 45],
                ['11:15', '12:00', false, null, 45],
                ['12:00', '12:40', true, 'Istirahat 2 (Ishoma)', 40],
                ['12:40', '13:20', false, null, 40],
                ['13:20', '14:00', false, null, 40],
                ['14:00', '14:40', false, null, 40],
                ['14:40', '15:20', false, null, 40],
            ];
            $this->buildDay('Selasa', $waktuSelasaKamis, $jadwal);
            $this->buildDay('Rabu', $waktuSelasaKamis, $jadwal);
            $this->buildDay('Kamis', $waktuSelasaKamis, $jadwal);

            // ─── JUMAT ───
            $waktuJumat = [
                ['07:00', '07:30', true, 'Senam / Jumat Bersih / Imtak', 30],
                ['07:30', '08:15', false, null, 45],
                ['08:15', '09:00', false, null, 45],
                ['09:00', '09:45', false, null, 45],
                ['09:45', '10:00', true, 'Istirahat 1', 15],
                ['10:00', '10:40', false, null, 40],
                ['10:40', '11:20', false, null, 40],
            ];
            $this->buildDay('Jumat', $waktuJumat, $jadwal);

            // Insert to DB
            foreach ($jadwal as $row) {
                JamPelajaran::create($row);
            }

            $this->info("✅ Jadwal berhasil dibuat! Total Slot Pelajaran Aktif: 44 Jam/Minggu.");
            $this->line("💡 Sekolah selesai jam 15:20 (Senin-Kamis) dan 11:20 (Jumat).");

        } catch (\Exception $e) {
            $this->error("❌ Gagal: " . $e->getMessage());
        }
    }

    private function buildDay($hari, $waktuArray, &$jadwal)
    {
        $jamKe = 1;
        foreach ($waktuArray as $w) {
            $jadwal[] = [
                'hari' => $hari,
                'jam_ke' => $w[2] ? 0 : $jamKe,
                'jam_mulai' => $w[0] . ':00',
                'jam_selesai' => $w[1] . ':00',
                'is_istirahat' => $w[2],
                'nama_kegiatan' => $w[3],
                'durasi_menit' => $w[4],
                'created_at' => now(),
                'updated_at' => now()
            ];
            if (!$w[2]) {
                $jamKe++;
            }
        }
    }
}
