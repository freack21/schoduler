<?php

namespace App\Livewire\Admin;

use App\Models\Jurusan;
use App\Models\Kurikulum;
use App\Models\Mapel;
use App\Models\Tingkat;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Data Kurikulum')]
class DataKurikulum extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;
    
    public int $tingkat_id = 0;
    public ?int $jurusan_id = null;
    public int $mapel_id = 0;

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'tingkat_id', 'jurusan_id', 'mapel_id']);
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'tingkat_id' => 'required|integer|min:1',
            'jurusan_id' => 'nullable|integer|exists:jurusan,id',
            'mapel_id' => 'required|integer|min:1',
        ]);

        // Check if already exists
        $exists = Kurikulum::where('tingkat_id', $this->tingkat_id)
            ->where('jurusan_id', $this->jurusan_id ?: null)
            ->where('mapel_id', $this->mapel_id)
            ->exists();

        if ($exists) {
            $this->addError('mapel_id', 'Kombinasi tingkat, jurusan, dan mapel sudah ada.');
            return;
        }

        Kurikulum::create([
            'tingkat_id' => $this->tingkat_id,
            'jurusan_id' => $this->jurusan_id ?: null,
            'mapel_id' => $this->mapel_id,
        ]);

        $this->showModal = false;
        $this->reset(['editingId', 'tingkat_id', 'jurusan_id', 'mapel_id']);
        $this->dispatch('toast', type: 'success', message: 'Data kurikulum berhasil disimpan!');
    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('swal-confirm',
            title: 'Hapus dari Kurikulum?',
            text: 'Mata pelajaran ini tidak akan lagi dijadwalkan untuk tingkat/jurusan ini.',
            confirmText: 'Ya, Hapus!',
            method: 'doDelete',
            payload: ['id' => $id]
        );
    }

    #[\Livewire\Attributes\On('doDelete')]
    public function doDelete(int $id): void
    {
        Kurikulum::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Data berhasil dihapus!');
    }

    public function render()
    {
        return view('livewire.admin.data-kurikulum', [
            'kurikulumList' => Kurikulum::with(['tingkat', 'jurusan', 'mapel'])
                ->join('tingkat', 'kurikulum.tingkat_id', '=', 'tingkat.id')
                ->join('mapel', 'kurikulum.mapel_id', '=', 'mapel.id')
                ->orderBy('tingkat.kode')
                ->orderBy('kurikulum.jurusan_id')
                ->orderBy('mapel.nama')
                ->select('kurikulum.*')
                ->get(),
            'tingkatList' => Tingkat::orderBy('kode')->get(),
            'jurusanList' => Jurusan::orderBy('kode')->get(),
            'mapelList' => Mapel::orderBy('nama')->get(),
        ]);
    }
}
