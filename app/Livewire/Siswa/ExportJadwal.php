<?php

namespace App\Livewire\Siswa;

use App\Models\Kelas;
use App\Models\Guru;
use App\Models\Mapel;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Ekspor Jadwal')]
class ExportJadwal extends Component
{
    public string $exportType = 'kelas';
    public array $selectedIds = [];

    public function download()
    {
        $this->validate([
            'selectedIds' => 'required|array|min:1',
        ], [
            'selectedIds.required' => 'Pilih minimal satu data untuk diekspor.',
        ]);

        $queryStr = http_build_query(['ids' => $this->selectedIds]);
        
        if ($this->exportType === 'kelas') {
            return redirect('/export/jadwal/kelas?' . $queryStr);
        } elseif ($this->exportType === 'guru') {
            return redirect('/export/jadwal/guru?' . $queryStr);
        } elseif ($this->exportType === 'mapel') {
            return redirect('/export/jadwal/mapel?' . $queryStr);
        }
    }

    public function updatedExportType()
    {
        $this->selectedIds = [];
    }

    public function render()
    {
        $list = [];
        if ($this->exportType === 'kelas') {
            $list = Kelas::orderBy('nama')->get();
        } elseif ($this->exportType === 'guru') {
            $list = Guru::with('user')->get()->sortBy('nama');
        } elseif ($this->exportType === 'mapel') {
            $list = Mapel::orderBy('nama')->get();
        }

        return view('livewire.siswa.export-jadwal', [
            'list' => $list
        ]);
    }
}
