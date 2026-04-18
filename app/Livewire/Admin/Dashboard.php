<?php

namespace App\Livewire\Admin;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Siswa;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard Admin')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard', [
            'totalGuru' => Guru::count(),
            'totalSiswa' => Siswa::count(),
            'totalKelas' => Kelas::count(),
            'totalMapel' => Mapel::count(),
        ]);
    }
}
