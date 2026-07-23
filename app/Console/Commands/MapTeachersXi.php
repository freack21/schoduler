<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GuruMapel;
use App\Models\Tingkat;
use Illuminate\Support\Facades\DB;

class MapTeachersXi extends Command
{
    protected $signature = 'app:map-teachers-xi';
    protected $description = 'Menyalin pemetaan guru mapel dari Tingkat XII ke Tingkat XI jika belum ada';

    public function handle()
    {
        $this->info("🔍 Memulai sinkronisasi pemetaan guru mapel dari Tingkat XII ke Tingkat XI...");

        $tingkatXI = Tingkat::where('nama', 'XI')->first();
        $tingkatXII = Tingkat::where('nama', 'XII')->first();

        if (!$tingkatXI || !$tingkatXII) {
            $this->error("❌ Gagal: Tingkat XI atau XII tidak ditemukan di database!");
            return;
        }

        $recordsXII = GuruMapel::where('tingkat_id', $tingkatXII->id)->get();
        if ($recordsXII->isEmpty()) {
            $this->warn("⚠️ Tidak ada pemetaan guru mapel untuk Tingkat XII.");
            return;
        }

        $createdCount = 0;
        DB::beginTransaction();
        try {
            foreach ($recordsXII as $record) {
                // Check if mapping already exists for Tingkat XI
                $exists = GuruMapel::where('guru_id', $record->guru_id)
                    ->where('mapel_id', $record->mapel_id)
                    ->where('tingkat_id', $tingkatXI->id)
                    ->exists();

                if (!$exists) {
                    GuruMapel::create([
                        'guru_id' => $record->guru_id,
                        'mapel_id' => $record->mapel_id,
                        'tingkat_id' => $tingkatXI->id,
                        'jurusan_id' => $record->jurusan_id,
                    ]);
                    $createdCount++;
                    $this->line("✅ Memetakan Guru ID {$record->guru_id} untuk Mapel ID {$record->mapel_id} ke Tingkat XI");
                }
            }

            DB::commit();
            $this->info("🎉 Selesai! Berhasil membuat {$createdCount} pemetaan guru baru untuk Tingkat XI.");
            $this->info("💡 Silakan jalankan 'php artisan app:reset-jadwal' lalu Generate Ulang!");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Gagal menyalin pemetaan: " . $e->getMessage());
        }
    }
}
