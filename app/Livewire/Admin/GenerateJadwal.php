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
    public int $inputMaxGenerations = 500;

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

    public function cancelGenerate(): void
    {
        $latest = ScheduleGeneration::latest('id')->first();
        if ($latest && in_array($latest->status, ['starting', 'running'])) {
            $latest->update(['status' => 'cancelled', 'message' => 'Proses generate jadwal dibatalkan secara manual.']);
            $this->refreshStatus();
        }
    }

    public function generate(): void
    {
        $genState = ScheduleGeneration::create([
            'status' => 'starting',
            'generation' => 0,
            'fitness' => 0,
            'violations' => 0,
            'dist_violations' => 0,
            'max_generations' => $this->inputMaxGenerations,
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

        // Prepare sequential map of JamPelajaran
        $jams = JamPelajaran::orderBy('jam_mulai')->get();
        $jamMap = [];
        $maxPos = 0;
        foreach ($hariAktif as $h) {
            $jamsForHari = $jams->where('hari', $h)->sortBy('jam_mulai')->values();
            foreach ($jamsForHari as $pos => $jam) {
                $jamMap[$h][$pos] = $jam;
                if ($pos > $maxPos) {
                    $maxPos = $pos;
                }
            }
        }

        if ($this->showResult && $this->status === 'done') {
            $kelasList = Kelas::orderBy('tingkat_id')->orderBy('nama')->get();

            foreach ($kelasList as $kelas) {
                $activeTahunAjaran = \App\Models\Pengaturan::activeTahunAjaran();
                $jadwal = Jadwal::with(['mapel', 'guru.user', 'jamPelajaran'])
                    ->where('kelas_id', $kelas->id)
                    ->where('tahun_ajaran', $activeTahunAjaran)
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
                for ($p = 0; $p <= $maxPos; $p++) {
                    foreach ($hariAktif as $h) {
                        $matrix[$p][$h] = [];
                    }
                }

                $mapelGlobalSeq = [];
                foreach ($jadwal as $entry) {
                    $jam = $entry->jamPelajaran;
                    $cleanHari = ucfirst(trim($jam->hari));
                    $kode = $entry->mapel->kode;
                    $mapelGlobalSeq[$kode] = ($mapelGlobalSeq[$kode] ?? 0) + 1;
                    
                    $pos = 0;
                    if (isset($jamMap[$cleanHari])) {
                        foreach ($jamMap[$cleanHari] as $pIdx => $jModel) {
                            if ($jModel->id === $jam->id) {
                                $pos = $pIdx;
                                break;
                            }
                        }
                    }

                    $matrix[$pos][$cleanHari][] = [
                        'mapel' => $kode,
                        'guru' => explode(',', $entry->guru->user->nama_lengkap)[0],
                        'seq' => $mapelGlobalSeq[$kode],
                        'total' => $mapelTotalCount[$kode],
                        'jam_mulai' => $jam->jam_mulai,
                        'jam_selesai' => $jam->jam_selesai,
                        'is_istirahat' => false,
                        'is_empty' => false,
                        'is_parallel' => $entry->mapel->is_parallel,
                    ];
                }

                // Fill blanks and breaks
                for ($p = 0; $p <= $maxPos; $p++) {
                    foreach ($hariAktif as $h) {
                        $jam = $jamMap[$h][$p] ?? null;
                        if ($jam) {
                            if ($jam->is_istirahat) {
                                $matrix[$p][$h][] = [
                                    'is_istirahat' => true,
                                    'is_empty' => false,
                                    'jam_mulai' => $jam->jam_mulai,
                                    'jam_selesai' => $jam->jam_selesai,
                                    'kegiatan' => $jam->nama_kegiatan ?? 'Istirahat',
                                ];
                            } else if (empty($matrix[$p][$h])) {
                                $matrix[$p][$h][] = [
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
            'maxPos' => $maxPos,
            'jamMap' => $jamMap,
        ]);
    }
}
