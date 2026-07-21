<?php

namespace App\Livewire\Siswa;

use App\Models\Jadwal;
use App\Models\JamPelajaran;
use App\Models\Siswa;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard Siswa')]
class Dashboard extends Component
{
    public string $selectedTahunAjaran = '';

    public function mount()
    {
        $this->selectedTahunAjaran = \App\Models\Pengaturan::activeTahunAjaran();
    }
    public function render()
    {
        $user = auth()->user();
        $siswa = Siswa::with(['kelas', 'kelas.siswa.user'])->where('user_id', $user->id)->first();

        $jadwal = collect();
        $teman = collect();

        if ($siswa) {
            $jadwal = Jadwal::with(['mapel', 'guru.user', 'jamPelajaran'])
                ->where('kelas_id', $siswa->kelas_id)
                ->where('tahun_ajaran', $this->selectedTahunAjaran)
                ->get();

            $teman = $siswa->kelas->siswa()
                ->with('user')
                ->where('siswa.id', '!=', $siswa->id)
                ->orderBy(
                    \App\Models\User::select('nama_lengkap')
                        ->whereColumn('users.id', 'siswa.user_id'),
                    'asc'
                )
                ->get();
        }

        $allHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $jamList = JamPelajaran::where('hari', 'Senin')->orderBy('jam_mulai')->get();

        return view('livewire.siswa.dashboard', [
            'siswa' => $siswa,
            'jadwal' => $jadwal,
            'teman' => $teman,
            'allHari' => $allHari,
            'jamList' => $jamList,
            'tahunAjaranList' => $this->getTahunAjaranList(),
        ]);
    }

    private function getTahunAjaranList(): array
    {
        $active = \App\Models\Pengaturan::activeTahunAjaran();
        $db = \App\Models\Jadwal::whereNotNull('tahun_ajaran')
            ->distinct()
            ->pluck('tahun_ajaran')
            ->toArray();
        if (!in_array($active, $db)) {
            $db[] = $active;
        }
        return array_unique($db);
    }
}
