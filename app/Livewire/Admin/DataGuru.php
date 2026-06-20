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

use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Data Guru')]
class DataGuru extends Component
{
    use WithPagination;
    use WithFileUploads;

    public string $search = '';
    public string $sortBy = 'nama_lengkap';
    public string $sortDir = 'asc';

    // Excel import
    public $excelFile;

    // Form fields
    public bool $showModal = false;
    public bool $showAssignModal = false;
    public bool $showModulModal = false;
    public ?int $editingId = null;
    public string $nip = '';
    public string $nama_lengkap = '';
    public string $password = '';

    // Assign mapel fields
    public ?int $assignGuruId = null;
    public string $assignGuruName = '';
    public int $selectedMapelId = 0;
    public ?int $selectedTingkatId = null;
    public ?int $selectedJurusanId = null;
    public array $assignments = [];

    // Modul ajar fields
    public string $viewingGuruName = '';
    public array $viewingModulAjars = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
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

    public function importExcel(): void
    {
        $this->validate([
            'excelFile' => 'required|file|max:10240',
        ], [
            'excelFile.required' => 'Pilih berkas Excel terlebih dahulu.',
        ]);

        $path = $this->excelFile->getRealPath();
        
        if ($xlsx = \Shuchkin\SimpleXLSX::parse($path)) {
            $rows = $xlsx->rows();
            if (count($rows) > 0) {
                // Skip header row
                array_shift($rows);
                
                $imported = 0;
                $skipped = 0;

                foreach ($rows as $row) {
                    if (empty($row[0]) || empty($row[1])) {
                        $skipped++;
                        continue;
                    }

                    $nip = trim($row[0]);
                    $nama = trim($row[1]);
                    $pass = !empty($row[2]) ? trim($row[2]) : '123456';

                    // Find or create user
                    $user = User::find($nip);
                    if ($user) {
                        $user->update([
                            'nama_lengkap' => $nama,
                        ]);
                        $imported++;
                    } else {
                        $user = User::create([
                            'id' => $nip,
                            'nama_lengkap' => $nama,
                            'password' => $pass,
                            'role' => 'guru',
                        ]);
                        Guru::create(['user_id' => $user->id]);
                        $imported++;
                    }
                }
                $this->dispatch('toast', type: 'success', message: "Berhasil mengimpor $imported guru! ($skipped baris dilewati karena kosong)");
            } else {
                $this->dispatch('toast', type: 'error', message: 'File Excel tidak memiliki baris data.');
            }
        } else {
            $this->dispatch('toast', type: 'error', message: 'Gagal menguraikan file Excel: ' . \Shuchkin\SimpleXLSX::parseError());
        }

        $this->excelFile = null;
    }

    public function openModulModal(int $guruId): void
    {
        $guru = Guru::with('user')->findOrFail($guruId);
        $this->viewingGuruName = $guru->user->nama_lengkap;
        $this->viewingModulAjars = \App\Models\ModulAjar::where('guru_id', $guru->id)
            ->with('mapel')
            ->get()
            ->toArray();
        $this->showModulModal = true;
    }

    // ── Assign Mapel ──
    public function openAssignModal(int $guruId): void
    {
        $guru = Guru::with(['user', 'guruMapel.mapel'])->findOrFail($guruId);
        $this->assignGuruId = $guruId;
        $this->assignGuruName = $guru->user->nama_lengkap;
        $this->reset(['selectedMapelId', 'selectedTingkatId', 'selectedJurusanId']);
        $this->loadAssignments();
        $this->showAssignModal = true;
    }

    public function loadAssignments(): void
    {
        $this->assignments = GuruMapel::where('guru_id', $this->assignGuruId)
            ->with(['mapel', 'tingkat', 'jurusan'])
            ->get()
            ->map(fn($gm) => [
                'id' => $gm->id,
                'mapel' => $gm->mapel->nama,
                'tingkat' => $gm->tingkat ? $gm->tingkat->nama : 'Semua Tingkat',
                'jurusan' => $gm->jurusan ? $gm->jurusan->nama : 'Umum',
            ])->toArray();
    }

    public function addAssignment(): void
    {
        $this->validate([
            'selectedMapelId' => 'required|integer|min:1',
            'selectedTingkatId' => 'nullable|integer',
            'selectedJurusanId' => 'nullable|integer',
        ]);

        $exists = GuruMapel::where('guru_id', $this->assignGuruId)
            ->where('mapel_id', $this->selectedMapelId)
            ->where('tingkat_id', $this->selectedTingkatId ?: null)
            ->where('jurusan_id', $this->selectedJurusanId ?: null)
            ->exists();

        if ($exists) {
            $this->addError('selectedMapelId', 'Kombinasi mapel, tingkat, dan jurusan sudah ada.');
            return;
        }

        GuruMapel::create([
            'guru_id' => $this->assignGuruId,
            'mapel_id' => $this->selectedMapelId,
            'tingkat_id' => $this->selectedTingkatId ?: null,
            'jurusan_id' => $this->selectedJurusanId ?: null,
        ]);

        $this->reset(['selectedMapelId', 'selectedTingkatId', 'selectedJurusanId']);
        $this->loadAssignments();
    }

    public function removeAssignment(int $guruMapelId): void
    {
        GuruMapel::where('id', $guruMapelId)->where('guru_id', $this->assignGuruId)->delete();
        $this->loadAssignments();
    }

    public function render()
    {
        $query = Guru::query()
            ->select('guru.*')
            ->join('users', 'guru.user_id', '=', 'users.id')
            ->addSelect([
                'total_jam' => GuruMapel::selectRaw('COALESCE(SUM(mapel.jam_per_minggu), 0)')
                    ->join('mapel', 'mapel.id', '=', 'guru_mapel.mapel_id')
                    ->whereColumn('guru_mapel.guru_id', 'guru.id')
            ])
            ->with(['user', 'guruMapel.mapel']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('users.nama_lengkap', 'like', "%{$this->search}%")
                  ->orWhere('users.id', 'like', "%{$this->search}%");
            });
        }

        if ($this->sortBy === 'nama_lengkap') {
            $query->orderBy('users.nama_lengkap', $this->sortDir);
        } elseif ($this->sortBy === 'beban_mengajar') {
            $query->orderBy('total_jam', $this->sortDir);
        }

        // Tiebreaker: ensure deterministic pagination when rows share the same sort value
        $query->orderBy('guru.id', 'asc');

        return view('livewire.admin.data-guru', [
            'guruList' => $query->paginate(10),
            'mapelList' => Mapel::orderBy('nama')->get(),
            'tingkatList' => \App\Models\Tingkat::orderBy('kode')->get(),
            'jurusanList' => \App\Models\Jurusan::orderBy('kode')->get(),
        ]);
    }
}
