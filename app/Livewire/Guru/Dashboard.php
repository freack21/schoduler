<?php

namespace App\Livewire\Guru;

use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\JamPelajaran;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard Guru')]
class Dashboard extends Component
{
    public string $activeTab = 'hari-ini';

    public function render()
    {
        $user = auth()->user();
        $guru = Guru::where('user_id', $user->id)->first();

        $guruMapels = $guru ? $guru->guruMapel()->with(['mapel', 'kelas'])->get() : collect();

        // Get schedule
        $hariMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];
        $hariIni = $hariMap[Carbon::now()->format('l')] ?? 'Senin';

        $jadwalHariIni = $guru ? Jadwal::with(['guruMapel.mapel', 'guruMapel.kelas', 'jamPelajaran'])
            ->whereHas('guruMapel', fn($q) => $q->where('guru_id', $guru->id))
            ->where('hari', $hariIni)
            ->get()
            ->sortBy(fn($j) => $j->jamPelajaran->jam_ke) : collect();

        $jadwalMingguan = $guru ? Jadwal::with(['guruMapel.mapel', 'guruMapel.kelas', 'jamPelajaran'])
            ->whereHas('guruMapel', fn($q) => $q->where('guru_id', $guru->id))
            ->get()
            ->groupBy('hari') : collect();

        $allHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $jamList = JamPelajaran::orderBy('jam_ke')->get();

        return view('livewire.guru.dashboard', [
            'guru' => $guru,
            'guruMapels' => $guruMapels,
            'jadwalHariIni' => $jadwalHariIni,
            'jadwalMingguan' => $jadwalMingguan,
            'hariIni' => $hariIni,
            'allHari' => $allHari,
            'jamList' => $jamList,
        ]);
    }
}
