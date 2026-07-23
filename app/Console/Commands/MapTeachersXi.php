<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GuruMapel;
use App\Models\Tingkat;
use Illuminate\Support\Facades\DB;

class MapTeachersXi extends Command
{
    protected $signature = 'app:map-teachers-xi';
    protected $description = 'Mendeteksi mapel kurikulum yang kosong guru di tingkat tertentu, lalu menyalin pemetaan guru dari tingkat lain jika ada';

    public function handle()
    {
        $this->info("🔍 Memulai sinkronisasi pemetaan guru mapel untuk semua tingkat...");

        $tingkats = \App\Models\Tingkat::all();
        $kelasList = \App\Models\Kelas::with(['jurusan', 'tingkat'])->get();
        $kurikulumList = \App\Models\Kurikulum::with('mapel')->get();
        $guruMapelAll = \App\Models\GuruMapel::all();

        // Cari mapel unik yang dibutuhkan per tingkat berdasarkan kurikulum kelas aktif
        $neededMappings = [];
        foreach ($kelasList as $kelas) {
            $kuriList = $kurikulumList->where('tingkat_id', $kelas->tingkat_id);
            if ($kelas->jurusan_id) {
                $kuriList = $kuriList->filter(fn($kuri) => is_null($kuri->jurusan_id) || $kuri->jurusan_id == $kelas->jurusan_id);
            } else {
                $kuriList = $kuriList->whereNull('jurusan_id');
            }
            foreach ($kuriList as $kuri) {
                $neededMappings[$kelas->tingkat_id][] = $kuri->mapel_id;
            }
        }

        $createdCount = 0;
        DB::beginTransaction();
        try {
            foreach ($neededMappings as $tingkatId => $mapelIds) {
                $tingkat = $tingkats->find($tingkatId);
                $mapelIds = array_unique($mapelIds);
                
                foreach ($mapelIds as $mapelId) {
                    $mapel = \App\Models\Mapel::find($mapelId);
                    
                    // Cek apakah mapel ini sudah punya guru pengampu di tingkat ini
                    $hasTeacher = $guruMapelAll->where('mapel_id', $mapelId)
                        ->where('tingkat_id', $tingkatId)
                        ->isNotEmpty();

                    if (!$hasTeacher) {
                        // Cari guru pengampu mapel ini di tingkat lain
                        $otherMappings = \App\Models\GuruMapel::with('guru.user')
                            ->where('mapel_id', $mapelId)
                            ->get();

                        if ($otherMappings->isNotEmpty()) {
                            // Ambil guru unik di tingkat lain
                            $uniqueGurus = $otherMappings->unique('guru_id');
                            foreach ($uniqueGurus as $other) {
                                // Cek double insert
                                $exists = \App\Models\GuruMapel::where('guru_id', $other->guru_id)
                                    ->where('mapel_id', $mapelId)
                                    ->where('tingkat_id', $tingkatId)
                                    ->exists();

                                if (!$exists) {
                                    \App\Models\GuruMapel::create([
                                        'guru_id' => $other->guru_id,
                                        'mapel_id' => $mapelId,
                                        'tingkat_id' => $tingkatId,
                                        'jurusan_id' => $other->jurusan_id,
                                    ]);
                                    $createdCount++;
                                    $this->line("✅ Memetakan Guru '{$other->guru->user->nama_lengkap}' untuk Mapel '{$mapel->nama}' ke Tingkat '{$tingkat->nama}'");
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();
            $this->info("🎉 Selesai! Berhasil membuat {$createdCount} pemetaan guru baru.");
            $this->info("💡 Silakan jalankan 'php artisan app:reset-jadwal' lalu Generate Ulang!");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Gagal sinkronisasi pemetaan: " . $e->getMessage());
        }
    }
}
