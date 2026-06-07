<?php

namespace App\Livewire\Admin;

use App\Models\Jurusan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Data Jurusan')]
class DataJurusan extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $kode = '';
    public string $nama = '';

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'kode', 'nama']);
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $jurusan = Jurusan::findOrFail($id);
        $this->editingId = $id;
        $this->kode = $jurusan->kode;
        $this->nama = $jurusan->nama;
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:50|unique:jurusan,kode,' . $this->editingId,
        ];

        $this->validate($rules);

        Jurusan::updateOrCreate(
            ['id' => $this->editingId],
            ['kode' => $this->kode, 'nama' => $this->nama]
        );

        $this->showModal = false;
        $this->reset(['editingId', 'kode', 'nama']);
        $this->dispatch('toast', type: 'success', message: 'Data jurusan berhasil disimpan!');
    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('swal-confirm',
            title: 'Hapus Jurusan?',
            text: 'Data jurusan akan dihapus. Pastikan tidak ada kelas yang menggunakan jurusan ini.',
            confirmText: 'Ya, Hapus!',
            method: 'doDelete',
            payload: ['id' => $id]
        );
    }

    #[\Livewire\Attributes\On('doDelete')]
    public function doDelete(int $id): void
    {
        try {
            Jurusan::findOrFail($id)->delete();
            $this->dispatch('toast', type: 'success', message: 'Jurusan berhasil dihapus!');
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: 'Gagal dihapus! Jurusan masih digunakan oleh kelas.');
        }
    }

    public function render()
    {
        return view('livewire.admin.data-jurusan', [
            'jurusanList' => Jurusan::withCount('kelas')->orderBy('kode')->get(),
        ]);
    }
}
