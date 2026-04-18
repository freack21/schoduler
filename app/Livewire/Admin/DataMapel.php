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
    public int $max_jam_per_hari = 2;

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'kode', 'nama', 'jam_per_minggu', 'max_jam_per_hari']);
        $this->jam_per_minggu = 2;
        $this->max_jam_per_hari = 2;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $mapel = Mapel::findOrFail($id);
        $this->editingId = $id;
        $this->kode = $mapel->kode;
        $this->nama = $mapel->nama;
        $this->jam_per_minggu = $mapel->jam_per_minggu;
        $this->max_jam_per_hari = $mapel->max_jam_per_hari;
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'kode' => 'required|string|max:10',
            'nama' => 'required|string|max:255',
            'jam_per_minggu' => 'required|integer|min:1|max:10',
            'max_jam_per_hari' => 'required|integer|min:1|max:10',
        ];

        if (!$this->editingId) {
            $rules['kode'] .= '|unique:mapel,kode';
        } else {
            $rules['kode'] .= '|unique:mapel,kode,' . $this->editingId;
        }

        $this->validate($rules);

        Mapel::updateOrCreate(
            ['id' => $this->editingId],
            ['kode' => strtoupper($this->kode), 'nama' => $this->nama, 'jam_per_minggu' => $this->jam_per_minggu, 'max_jam_per_hari' => $this->max_jam_per_hari]
        );

        $this->showModal = false;
        $this->reset(['editingId', 'kode', 'nama', 'jam_per_minggu', 'max_jam_per_hari']);
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
