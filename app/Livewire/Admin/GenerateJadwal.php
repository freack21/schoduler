<?php

namespace App\Livewire\Admin;

use App\Jobs\GenerateScheduleJob;
use App\Models\Jadwal;
use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\Pengaturan;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Generate Jadwal')]
class GenerateJadwal extends Component
{
    public string $status = 'idle';
    public int $generation = 0;
    public float $fitness = 0;
    public int $violations = 0;
    public int $distViolations = 0;
    public string $message = '';
    public bool $showResult = false;
    public int $maxGenerations = 1000;

    // Hari aktif settings
    public array $allHariOptions = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    public array $selectedHari = [];

    public function mount(): void
    {
        $this->refreshStatus();
        $this->selectedHari = Pengaturan::getHariAktif();
    }

    public function refreshStatus(): void
    {
        $this->status = Cache::get('ga_status', 'idle');
        $this->generation = Cache::get('ga_generation', 0);
        $this->fitness = Cache::get('ga_fitness', 0);
        $this->violations = Cache::get('ga_violations', 0);
        $this->distViolations = Cache::get('ga_dist_violations', 0);
        $this->message = Cache::get('ga_message', '');
        $this->maxGenerations = Cache::get('ga_max_generations', 1000);

        if ($this->status === 'done') {
            $this->showResult = true;
        }
    }

    public function saveHariAktif(): void
    {
        if (empty($this->selectedHari)) {
            $this->dispatch('toast', type: 'error', message: 'Pilih minimal 1 hari aktif!');
            return;
        }

        // Sort by correct day order
        $ordered = array_intersect($this->allHariOptions, $this->selectedHari);
        Pengaturan::setValue('hari_aktif', implode(',', $ordered), 'Hari aktif penjadwalan');
        $this->selectedHari = array_values($ordered);
        $this->dispatch('toast', type: 'success', message: 'Hari aktif berhasil disimpan!');
    }

    public function generate(): void
    {
        Cache::put('ga_status', 'starting', 600);
        Cache::put('ga_generation', 0, 600);
        Cache::put('ga_fitness', 0, 600);
        Cache::put('ga_violations', 0, 600);
        Cache::put('ga_dist_violations', 0, 600);
        Cache::put('ga_message', '', 600);

        $this->status = 'starting';
        $this->showResult = false;

        GenerateScheduleJob::dispatch();
    }

    public function resetGenerate(): void
    {
        Cache::forget('ga_status');
        Cache::forget('ga_generation');
        Cache::forget('ga_fitness');
        Cache::forget('ga_violations');
        Cache::forget('ga_dist_violations');
        Cache::forget('ga_message');

        $this->status = 'idle';
        $this->generation = 0;
        $this->fitness = 0;
        $this->violations = 0;
        $this->distViolations = 0;
        $this->message = '';
        $this->showResult = false;
    }

    public function render()
    {
        $jadwalData = [];
        $hariAktif = Pengaturan::getHariAktif();

        if ($this->showResult && $this->status === 'done') {
            $kelasList = Kelas::orderBy('tingkat_id')->orderBy('nama')->get();
            $jamList = JamPelajaran::orderBy('jam_ke')->get();

            foreach ($kelasList as $kelas) {
                $jadwal = Jadwal::with(['guruMapel.mapel', 'guruMapel.guru.user', 'jamPelajaran'])
                    ->whereHas('guruMapel', fn($q) => $q->where('kelas_id', $kelas->id))
                    ->get();

                // First pass: count total occurrences of each mapel per day
                $mapelCountPerDay = []; // "hari-mapel_kode" => total
                foreach ($hariAktif as $h) {
                    foreach ($jamList as $jam) {
                        $entry = $jadwal->first(fn($j) => $j->hari === $h && $j->jam_pelajaran_id === $jam->id);
                        if ($entry) {
                            $key = $h . '-' . $entry->guruMapel->mapel->kode;
                            $mapelCountPerDay[$key] = ($mapelCountPerDay[$key] ?? 0) + 1;
                        }
                    }
                }

                // Second pass: build matrix with occurrence number
                $matrix = [];
                $mapelSeqPerDay = []; // running counter per day-mapel
                foreach ($hariAktif as $h) {
                    foreach ($jamList as $jam) {
                        if ($jam->is_istirahat) continue;

                        $entry = $jadwal->first(fn($j) => $j->hari === $h && $j->jam_pelajaran_id === $jam->id);
                        if ($entry) {
                            $kode = $entry->guruMapel->mapel->kode;
                            $key = $h . '-' . $kode;
                            $mapelSeqPerDay[$key] = ($mapelSeqPerDay[$key] ?? 0) + 1;
                            $total = $mapelCountPerDay[$key];
                            $matrix[$h][$jam->id] = [
                                'mapel' => $kode,
                                'guru' => $entry->guruMapel->guru->user->nama_lengkap,
                                'seq' => $mapelSeqPerDay[$key],   // x1, x2, ...
                                'total' => $total,                 // total on this day
                            ];
                        } else {
                            $matrix[$h][$jam->id] = null;
                        }
                    }
                }

                $jadwalData[] = [
                    'kelas' => $kelas->nama,
                    'matrix' => $matrix,
                ];
            }
        }

        return view('livewire.admin.generate-jadwal', [
            'jadwalData' => $jadwalData,
            'hari' => $hariAktif,
            'jamList' => JamPelajaran::orderBy('jam_ke')->get(),
        ]);
    }
}
