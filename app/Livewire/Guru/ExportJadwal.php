<?php

namespace App\Livewire\Guru;

use App\Models\Kelas;
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
        $url = "/export/jadwal/{$this->exportType}?" . $queryStr;
        
        $this->dispatch('open-new-tab', url: $url);
    }

    public function toggleSelectAll()
    {
        $list = [];
        if ($this->exportType === 'kelas') {
            $list = Kelas::pluck('id')->toArray();
        } elseif ($this->exportType === 'mapel') {
            $list = Mapel::pluck('id')->toArray();
        }

        if (count($this->selectedIds) === count($list)) {
            $this->selectedIds = [];
        } else {
            $this->selectedIds = $list;
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
        } elseif ($this->exportType === 'mapel') {
            $list = Mapel::orderBy('nama')->get();
        }

        return view('livewire.guru.export-jadwal', [
            'list' => $list
        ]);
    }
}
