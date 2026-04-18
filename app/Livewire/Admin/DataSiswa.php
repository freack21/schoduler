<?php

namespace App\Livewire\Admin;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Data Siswa')]
class DataSiswa extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $nisn = '';
    public string $nama_lengkap = '';
    public string $password = '';
    public int $kelas_id = 0;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'nisn', 'nama_lengkap', 'password', 'kelas_id']);
        $this->showModal = true;
    }

    public function openEditModal(int $siswaId): void
    {
        $siswa = Siswa::with('user')->findOrFail($siswaId);
        $this->editingId = $siswaId;
        $this->nisn = $siswa->user->id;
        $this->nama_lengkap = $siswa->user->nama_lengkap;
        $this->kelas_id = $siswa->kelas_id;
        $this->password = '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'nisn' => 'required|string|max:255',
            'nama_lengkap' => 'required|string|max:255',
            'kelas_id' => 'required|integer|min:1',
        ];

        if (!$this->editingId) {
            $rules['nisn'] .= '|unique:users,id';
            $rules['password'] = 'required|string|min:6';
        } else {
            $rules['password'] = 'nullable|string|min:6';
        }

        $this->validate($rules);

        if ($this->editingId) {
            $siswa = Siswa::with('user')->findOrFail($this->editingId);
            $siswa->user->update([
                'nama_lengkap' => $this->nama_lengkap,
                ...($this->password ? ['password' => $this->password] : []),
            ]);
            $siswa->update(['kelas_id' => $this->kelas_id]);
        } else {
            $user = User::create([
                'id' => $this->nisn,
                'nama_lengkap' => $this->nama_lengkap,
                'password' => $this->password,
                'role' => 'siswa',
            ]);
            Siswa::create(['user_id' => $user->id, 'kelas_id' => $this->kelas_id]);
        }

        $this->showModal = false;
        $this->reset(['editingId', 'nisn', 'nama_lengkap', 'password', 'kelas_id']);
        $this->dispatch('toast', type: 'success', message: 'Data siswa berhasil disimpan!');
    }

    public function confirmDelete(int $siswaId): void
    {
        $this->dispatch('swal-confirm',
            title: 'Hapus Siswa?',
            text: 'Data siswa akan dihapus permanen.',
            confirmText: 'Ya, Hapus!',
            method: 'doDelete',
            payload: ['id' => $siswaId]
        );
    }

    #[\Livewire\Attributes\On('doDelete')]
    public function doDelete(int $id): void
    {
        $siswa = Siswa::findOrFail($id);
        User::where('id', $siswa->user_id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Siswa berhasil dihapus!');
    }

    public function render()
    {
        $siswa = Siswa::with(['user', 'kelas'])
            ->whereHas('user', fn($q) => $q->where('nama_lengkap', 'like', "%{$this->search}%")->orWhere('id', 'like', "%{$this->search}%"))
            ->paginate(10);

        return view('livewire.admin.data-siswa', [
            'siswaList' => $siswa,
            'kelasList' => Kelas::with('tingkat')->orderBy('tingkat_id')->orderBy('nama')->get(),
        ]);
    }
}
