<?php

namespace App\Livewire\Admin;

use App\Jobs\GenerateScheduleJob;
use App\Models\Jadwal;
use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\ScheduleGeneration;
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
        $latest = ScheduleGeneration::latest()->first();
        
        if ($latest) {
            $this->status = $latest->status;
            $this->generation = $latest->generation;
            $this->fitness = $latest->fitness;
            $this->violations = $latest->violations;
            $this->distViolations = $latest->dist_violations;
            $this->message = $latest->message ?? '';
            $this->maxGenerations = $latest->max_generations;
        } else {
            $this->status = 'idle';
            $this->generation = 0;
            $this->fitness = 0;
            $this->violations = 0;
            $this->distViolations = 0;
            $this->message = '';
        }

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
        $genState = ScheduleGeneration::create([
            'status' => 'starting',
            'generation' => 0,
            'fitness' => 0,
            'violations' => 0,
            'dist_violations' => 0,
            'max_generations' => 300, // Matching job default
            'started_at' => now(),
        ]);

        $this->refreshStatus();
        $this->showResult = false;

        GenerateScheduleJob::dispatch($genState->id);
    }

    public function resetGenerate(): void
    {
        // We can just clear the latest record or mark it as idle, but since we keep history,
        // we can just delete all or let the user start a new one. 
        // For 'reset' behavior, we can just set local state to idle.
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

                // Count total occurrences of each mapel across ALL days (for this kelas)
                $mapelTotalCount = []; // mapel_kode => total across week
                $mapelJamPerMinggu = []; // mapel_kode => jam_per_minggu from DB
                foreach ($jadwal as $entry) {
                    $kode = $entry->guruMapel->mapel->kode;
                    $mapelTotalCount[$kode] = ($mapelTotalCount[$kode] ?? 0) + 1;
                    $mapelJamPerMinggu[$kode] = $entry->guruMapel->mapel->jam_per_minggu;
                }

                // Build matrix with GLOBAL sequence number (across all days)
                $matrix = [];
                $mapelGlobalSeq = []; // mapel_kode => running counter across all days
                foreach ($hariAktif as $h) {
                    foreach ($jamList as $jam) {
                        if ($jam->is_istirahat) continue;

                        $entry = $jadwal->first(fn($j) => $j->hari === $h && $j->jam_pelajaran_id === $jam->id);
                        if ($entry) {
                            $kode = $entry->guruMapel->mapel->kode;
                            $mapelGlobalSeq[$kode] = ($mapelGlobalSeq[$kode] ?? 0) + 1;
                            $totalJam = $mapelJamPerMinggu[$kode] ?? $mapelTotalCount[$kode];
                            $matrix[$h][$jam->id] = [
                                'mapel' => $kode,
                                'guru'  => $entry->guruMapel->guru->user->nama_lengkap,
                                'seq'   => $mapelGlobalSeq[$kode], // global: 1, 2, 3, 4
                                'total' => $totalJam,               // jam_per_minggu
                            ];
                        } else {
                            $matrix[$h][$jam->id] = null;
                        }
                    }
                }

                $jadwalData[] = [
                    'kelas'  => $kelas->nama,
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
