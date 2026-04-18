<?php

namespace App\Livewire\Admin;

use App\Models\JamPelajaran;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Jam Pelajaran')]
class DataJamPelajaran extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;
    public int $jam_ke = 1;
    public string $jam_mulai = '07:00';
    public string $jam_selesai = '07:45';
    public bool $is_istirahat = false;

    public function openCreateModal(): void
    {
        $lastJam = JamPelajaran::orderBy('jam_ke', 'desc')->first();
        $this->editingId = null;
        $this->jam_ke = $lastJam ? $lastJam->jam_ke + 1 : 1;
        $this->jam_mulai = $lastJam ? $lastJam->jam_selesai : '07:00';
        $this->jam_selesai = '';
        $this->is_istirahat = false;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $jam = JamPelajaran::findOrFail($id);
        $this->editingId = $id;
        $this->jam_ke = $jam->jam_ke;
        $this->jam_mulai = substr($jam->jam_mulai, 0, 5);
        $this->jam_selesai = substr($jam->jam_selesai, 0, 5);
        $this->is_istirahat = $jam->is_istirahat;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'jam_ke' => 'required|integer|min:1',
            'jam_mulai' => 'required|string',
            'jam_selesai' => 'required|string',
        ]);

        JamPelajaran::updateOrCreate(
            ['id' => $this->editingId],
            [
                'jam_ke' => $this->jam_ke,
                'jam_mulai' => $this->jam_mulai,
                'jam_selesai' => $this->jam_selesai,
                'is_istirahat' => $this->is_istirahat,
            ]
        );

        $this->showModal = false;
        $this->dispatch('toast', type: 'success', message: 'Jam pelajaran berhasil disimpan!');
    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('swal-confirm',
            title: 'Hapus Jam Pelajaran?',
            text: 'Jam pelajaran ini akan dihapus.',
            confirmText: 'Ya, Hapus!',
            method: 'doDelete',
            payload: ['id' => $id]
        );
    }

    #[\Livewire\Attributes\On('doDelete')]
    public function doDelete(int $id): void
    {
        JamPelajaran::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Jam pelajaran berhasil dihapus!');
    }

    public function render()
    {
        return view('livewire.admin.data-jam-pelajaran', [
            'jamList' => JamPelajaran::orderBy('jam_ke')->get(),
        ]);
    }
}
