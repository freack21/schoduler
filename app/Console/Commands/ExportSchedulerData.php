<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\GuruMapel;
use App\Models\JamPelajaran;
use App\Models\Mapel;
use App\Models\Guru;
use App\Models\User;

class ExportSchedulerData extends Command
{
    protected $signature = 'app:export-scheduler-data';
    protected $description = 'Mengekspor seluruh data penjadwalan ke file JSON untuk dianalisis oleh AI';

    public function handle()
    {
        $this->info("📦 Mengumpulkan data penjadwalan...");

        $data = [
            'kelas' => Kelas::all(),
            'kurikulum' => Kurikulum::all(),
            'guru_mapel' => GuruMapel::all(),
            'jam_pelajaran' => JamPelajaran::all(),
            'mapel' => Mapel::all(),
            'guru' => Guru::with('user')->get()->map(fn($g) => [
                'id' => $g->id,
                'user_id' => $g->user_id,
                'nama' => $g->user->nama_lengkap ?? $g->nama,
            ]),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT);
        $path = base_path('scheduler_data_dump.json');
        file_put_contents($path, $json);

        $this->info("✅ Berhasil mengekspor data ke: {$path}");
        $this->line("Silakan copy isi file tersebut atau kirimkan file tersebut ke saya!");
    }
}
