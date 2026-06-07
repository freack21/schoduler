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

    public function mount(): void
    {
        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        $latest = ScheduleGeneration::latest('id')->first();
        
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

    public function updateHariAktif(): void
    {
        // Removed as hari_aktif is now determined dynamically
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
        $allDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $dbDays = JamPelajaran::select('hari')->distinct()->pluck('hari')->toArray();
        $hariAktif = array_values(array_intersect($allDays, $dbDays));
        $maxJamKe = JamPelajaran::max('jam_ke') ?? 0;

        if ($this->showResult && $this->status === 'done') {
            $kelasList = Kelas::orderBy('tingkat_id')->orderBy('nama')->get();
            
            $jams = JamPelajaran::all();
            $jamMap = [];
            foreach($jams as $jam) {
                $jamMap[$jam->hari][$jam->jam_ke] = $jam;
            }

            foreach ($kelasList as $kelas) {
                $jadwal = Jadwal::with(['mapel', 'guru.user', 'jamPelajaran'])
                    ->where('kelas_id', $kelas->id)
                    ->get()
                    ->sortBy(function ($entry) use ($hariAktif) {
                        $dayIndex = array_search($entry->hari, $hariAktif);
                        if ($dayIndex === false) $dayIndex = 99;
                        return $dayIndex * 100 + $entry->jamPelajaran->jam_ke;
                    })->values();

                $mapelTotalCount = [];
                foreach ($jadwal as $entry) {
                    $kode = $entry->mapel->kode;
                    $mapelTotalCount[$kode] = ($mapelTotalCount[$kode] ?? 0) + 1;
                }

                $matrix = [];
                for ($i = 1; $i <= $maxJamKe; $i++) {
                    foreach ($hariAktif as $h) {
                        $matrix[$i][$h] = [];
                    }
                }

                $mapelGlobalSeq = [];
                foreach ($jadwal as $entry) {
                    $jam = $entry->jamPelajaran;
                    $cleanHari = ucfirst(trim($jam->hari));
                    $kode = $entry->mapel->kode;
                    $mapelGlobalSeq[$kode] = ($mapelGlobalSeq[$kode] ?? 0) + 1;
                    
                    $matrix[$jam->jam_ke][$cleanHari][] = [
                        'mapel' => $kode,
                        'guru' => explode(',', $entry->guru->user->nama_lengkap)[0],
                        'seq' => $mapelGlobalSeq[$kode],
                        'total' => $mapelTotalCount[$kode],
                        'jam_mulai' => $jam->jam_mulai,
                        'jam_selesai' => $jam->jam_selesai,
                        'is_istirahat' => false,
                        'is_empty' => false,
                    ];
                }

                // Fill blanks and breaks
                for ($i = 1; $i <= $maxJamKe; $i++) {
                    foreach ($hariAktif as $h) {
                        $jam = $jamMap[$h][$i] ?? null;
                        if ($jam) {
                            if ($jam->is_istirahat) {
                                $matrix[$i][$h][] = [
                                    'is_istirahat' => true,
                                    'is_empty' => false,
                                    'jam_mulai' => $jam->jam_mulai,
                                    'jam_selesai' => $jam->jam_selesai,
                                ];
                            } else if (empty($matrix[$i][$h])) {
                                $matrix[$i][$h][] = [
                                    'is_istirahat' => false,
                                    'is_empty' => true,
                                    'jam_mulai' => $jam->jam_mulai,
                                    'jam_selesai' => $jam->jam_selesai,
                                ];
                            }
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
            'maxJamKe' => $maxJamKe,
        ]);
    }
}
