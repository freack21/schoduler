<?php

namespace App\Livewire\Admin;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Tingkat;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Data Kelas')]
class DataKelas extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $nama = '';
    public int $tingkat_id = 0;
    public ?int $jurusan_id = null;

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'nama', 'tingkat_id', 'jurusan_id']);
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $kelas = Kelas::findOrFail($id);
        $this->editingId = $id;
        $this->nama = $kelas->nama;
        $this->tingkat_id = $kelas->tingkat_id;
        $this->jurusan_id = $kelas->jurusan_id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'nama' => 'required|string|max:255',
            'tingkat_id' => 'required|integer|min:1',
            'jurusan_id' => 'nullable|integer|exists:jurusan,id',
        ]);

        Kelas::updateOrCreate(
            ['id' => $this->editingId],
            ['nama' => $this->nama, 'tingkat_id' => $this->tingkat_id, 'jurusan_id' => $this->jurusan_id ?: null]
        );

        $this->showModal = false;
        $this->reset(['editingId', 'nama', 'tingkat_id', 'jurusan_id']);
        $this->dispatch('toast', type: 'success', message: 'Data kelas berhasil disimpan!');
    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('swal-confirm',
            title: 'Hapus Kelas?',
            text: 'Pastikan tidak ada siswa di kelas ini.',
            confirmText: 'Ya, Hapus!',
            method: 'doDelete',
            payload: ['id' => $id]
        );
    }

    #[\Livewire\Attributes\On('doDelete')]
    public function doDelete(int $id): void
    {
        Kelas::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Kelas berhasil dihapus!');
    }

    public function render()
    {
        return view('livewire.admin.data-kelas', [
            'kelasList' => Kelas::with(['tingkat', 'jurusan', 'siswa'])->orderBy('tingkat_id')->orderBy('nama')->get(),
            'tingkatList' => Tingkat::orderBy('kode')->get(),
            'jurusanList' => Jurusan::orderBy('kode')->get(),
        ]);
    }
}
