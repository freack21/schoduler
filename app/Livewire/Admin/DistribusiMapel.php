<?php

namespace App\Livewire\Admin;

use App\Models\Mapel;
use App\Models\Kelas;
use App\Models\GuruMapel;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Pemetaan Distribusi Mapel')]
class DistribusiMapel extends Component
{
    public function render()
    {
        // Get all mapels with their guru assignments
        $mapels = Mapel::orderBy('nama')->get();
        $kelasList = Kelas::with('tingkat')->orderBy('tingkat_id')->orderBy('nama')->get();
        
        $assignments = GuruMapel::with(['guru.user', 'kelas'])->get();
        
        // Group assignments by mapel_id and then by kelas_id
        $grouped = [];
        foreach ($assignments as $a) {
            $grouped[$a->mapel_id][$a->kelas_id][] = $a->guru->user->nama_lengkap;
        }

        return view('livewire.admin.distribusi-mapel', [
            'mapels' => $mapels,
            'kelasList' => $kelasList,
            'groupedAssignments' => $grouped,
        ]);
    }
}
