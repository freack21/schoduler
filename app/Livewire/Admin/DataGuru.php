<?php

namespace App\Livewire\Admin;

use App\Models\Guru;
use App\Models\GuruMapel;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Data Guru')]
class DataGuru extends Component
{
    use WithPagination;

    public string $search = '';

    // Form fields
    public bool $showModal = false;
    public bool $showAssignModal = false;
    public ?int $editingId = null;
    public string $nip = '';
    public string $nama_lengkap = '';
    public string $password = '';

    // Assign mapel fields
    public ?int $assignGuruId = null;
    public string $assignGuruName = '';
    public int $selectedMapelId = 0;
    public int $selectedKelasId = 0;
    public array $assignments = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'nip', 'nama_lengkap', 'password']);
        $this->showModal = true;
    }

    public function openEditModal(int $guruId): void
    {
        $guru = Guru::with('user')->findOrFail($guruId);
        $this->editingId = $guruId;
        $this->nip = $guru->user->id;
        $this->nama_lengkap = $guru->user->nama_lengkap;
        $this->password = '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'nip' => 'required|string|max:255',
            'nama_lengkap' => 'required|string|max:255',
        ];

        if (!$this->editingId) {
            $rules['nip'] .= '|unique:users,id';
            $rules['password'] = 'required|string|min:6';
        } else {
            $rules['password'] = 'nullable|string|min:6';
        }

        $this->validate($rules);

        if ($this->editingId) {
            $guru = Guru::with('user')->findOrFail($this->editingId);
            $guru->user->update([
                'nama_lengkap' => $this->nama_lengkap,
                ...($this->password ? ['password' => $this->password] : []),
            ]);
        } else {
            $user = User::create([
                'id' => $this->nip,
                'nama_lengkap' => $this->nama_lengkap,
                'password' => $this->password,
                'role' => 'guru',
            ]);
            Guru::create(['user_id' => $user->id]);
        }

        $this->showModal = false;
        $this->reset(['editingId', 'nip', 'nama_lengkap', 'password']);
        $this->dispatch('toast', type: 'success', message: 'Data guru berhasil disimpan!');
    }

    public function confirmDelete(int $guruId): void
    {
        $this->dispatch('swal-confirm', 
            title: 'Hapus Guru?',
            text: 'Data guru dan semua assignment mapelnya akan dihapus.',
            confirmText: 'Ya, Hapus!',
            method: 'doDelete',
            payload: ['id' => $guruId]
        );
    }

    #[\Livewire\Attributes\On('doDelete')]
    public function doDelete(int $id): void
    {
        $guru = Guru::findOrFail($id);
        User::where('id', $guru->user_id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Guru berhasil dihapus!');
    }

    // ── Assign Mapel ──
    public function openAssignModal(int $guruId): void
    {
        $guru = Guru::with(['user', 'guruMapel.mapel', 'guruMapel.kelas'])->findOrFail($guruId);
        $this->assignGuruId = $guruId;
        $this->assignGuruName = $guru->user->nama_lengkap;
        $this->loadAssignments();
        $this->showAssignModal = true;
    }

    public function loadAssignments(): void
    {
        $this->assignments = GuruMapel::where('guru_id', $this->assignGuruId)
            ->with(['mapel', 'kelas'])
            ->get()
            ->map(fn($gm) => [
                'id' => $gm->id,
                'mapel' => $gm->mapel->nama,
                'kelas' => $gm->kelas->nama,
            ])->toArray();
    }

    public function addAssignment(): void
    {
        $this->validate([
            'selectedMapelId' => 'required|integer|min:1',
            'selectedKelasId' => 'required|integer|min:1',
        ]);

        $exists = GuruMapel::where('guru_id', $this->assignGuruId)
            ->where('mapel_id', $this->selectedMapelId)
            ->where('kelas_id', $this->selectedKelasId)
            ->exists();

        if ($exists) {
            $this->addError('selectedMapelId', 'Kombinasi mapel dan kelas sudah ada.');
            return;
        }

        GuruMapel::create([
            'guru_id' => $this->assignGuruId,
            'mapel_id' => $this->selectedMapelId,
            'kelas_id' => $this->selectedKelasId,
        ]);

        $this->selectedMapelId = 0;
        $this->selectedKelasId = 0;
        $this->loadAssignments();
    }

    public function removeAssignment(int $guruMapelId): void
    {
        GuruMapel::where('id', $guruMapelId)->where('guru_id', $this->assignGuruId)->delete();
        $this->loadAssignments();
    }

    public function render()
    {
        $guru = Guru::with(['user', 'guruMapel.mapel'])
            ->whereHas('user', function ($q) {
                $q->where('nama_lengkap', 'like', "%{$this->search}%")
                  ->orWhere('id', 'like', "%{$this->search}%");
            })
            ->paginate(10);

        return view('livewire.admin.data-guru', [
            'guruList' => $guru,
            'mapelList' => Mapel::orderBy('nama')->get(),
            'kelasList' => Kelas::with('tingkat')->orderBy('tingkat_id')->orderBy('nama')->get(),
        ]);
    }
}
