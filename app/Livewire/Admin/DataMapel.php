<?php

namespace App\Livewire\Admin;

use App\Models\Mapel;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Data Mata Pelajaran')]
class DataMapel extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $kode = '';
    public string $nama = '';
    public int $jam_per_minggu = 2;
    public int $jam_per_hari = 2;
    public bool $is_parallel = false;
    public ?string $kelompok_paralel = null;

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'kode', 'nama', 'jam_per_minggu', 'jam_per_hari', 'is_parallel', 'kelompok_paralel']);
        $this->jam_per_minggu = 2;
        $this->jam_per_hari = 2;
        $this->is_parallel = false;
        $this->kelompok_paralel = null;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $mapel = Mapel::findOrFail($id);
        $this->editingId = $id;
        $this->kode = $mapel->kode;
        $this->nama = $mapel->nama;
        $this->jam_per_minggu = $mapel->jam_per_minggu;
        $this->jam_per_hari = $mapel->jam_per_hari;
        $this->is_parallel = $mapel->is_parallel;
        $this->kelompok_paralel = $mapel->kelompok_paralel;
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'kode' => 'required|string|max:10',
            'nama' => 'required|string|max:255',
            'jam_per_minggu' => 'required|integer|min:1|max:10',
            'jam_per_hari' => 'required|integer|min:1|max:10',
            'is_parallel' => 'boolean',
            'kelompok_paralel' => 'nullable|string|max:255',
        ];

        if (!$this->editingId) {
            $rules['kode'] .= '|unique:mapel,kode';
        } else {
            $rules['kode'] .= '|unique:mapel,kode,' . $this->editingId;
        }

        $this->validate($rules);

        Mapel::updateOrCreate(
            ['id' => $this->editingId],
            [
                'kode' => strtoupper($this->kode), 
                'nama' => $this->nama, 
                'jam_per_minggu' => $this->jam_per_minggu, 
                'jam_per_hari' => $this->jam_per_hari,
                'is_parallel' => $this->is_parallel,
                'kelompok_paralel' => $this->is_parallel ? $this->kelompok_paralel : null
            ]
        );

        $this->showModal = false;
        $this->reset(['editingId', 'kode', 'nama', 'jam_per_minggu', 'jam_per_hari', 'is_parallel', 'kelompok_paralel']);
        $this->dispatch('toast', type: 'success', message: 'Data mapel berhasil disimpan!');
    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('swal-confirm',
            title: 'Hapus Mata Pelajaran?',
            text: 'Data mapel akan dihapus permanen.',
            confirmText: 'Ya, Hapus!',
            method: 'doDelete',
            payload: ['id' => $id]
        );
    }

    #[\Livewire\Attributes\On('doDelete')]
    public function doDelete(int $id): void
    {
        Mapel::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Mapel berhasil dihapus!');
    }

    public function render()
    {
        return view('livewire.admin.data-mapel', [
            'mapelList' => Mapel::orderBy('nama')->get(),
        ]);
    }
}
