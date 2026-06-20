<?php

namespace App\Livewire\Admin;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Data Siswa & Kelas')]
class DataSiswa extends Component
{
    use WithPagination;
    use WithFileUploads;

    public string $search = '';
    public string $sortBy = 'nama_lengkap';
    public string $sortDir = 'asc';

    // Integrated navigation
    public ?int $selectedKelasId = null;

    // Excel import
    public $excelFile;

    // Student Form fields
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $nisn = '';
    public string $nama_lengkap = '';
    public string $password = '';
    public int $kelas_id = 0;

    // Class Form fields
    public bool $showKelasModal = false;
    public ?int $editingKelasId = null;
    public string $kelas_nama = '';
    public ?int $kelas_tingkat_id = null;
    public ?int $kelas_jurusan_id = null;

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

    public function selectKelas(?int $id): void
    {
        $this->selectedKelasId = $id;
        $this->resetPage();
        $this->reset(['search']);
    }

    // ── Class CRUD ──
    public function openCreateKelasModal(): void
    {
        $this->reset(['editingKelasId', 'kelas_nama', 'kelas_tingkat_id', 'kelas_jurusan_id']);
        $this->showKelasModal = true;
    }

    public function openEditKelasModal(int $id): void
    {
        $kelas = Kelas::findOrFail($id);
        $this->editingKelasId = $id;
        $this->kelas_nama = $kelas->nama;
        $this->kelas_tingkat_id = $kelas->tingkat_id;
        $this->kelas_jurusan_id = $kelas->jurusan_id;
        $this->showKelasModal = true;
    }

    public function saveKelas(): void
    {
        $this->validate([
            'kelas_nama' => 'required|string|max:255',
            'kelas_tingkat_id' => 'required|exists:tingkat,id',
            'kelas_jurusan_id' => 'nullable|exists:jurusan,id',
        ], [
            'kelas_nama.required' => 'Nama kelas wajib diisi.',
            'kelas_tingkat_id.required' => 'Pilih tingkat kelas.',
        ]);

        if ($this->editingKelasId) {
            $kelas = Kelas::findOrFail($this->editingKelasId);
            $kelas->update([
                'nama' => $this->kelas_nama,
                'tingkat_id' => $this->kelas_tingkat_id,
                'jurusan_id' => $this->kelas_jurusan_id ?: null,
            ]);
        } else {
            Kelas::create([
                'nama' => $this->kelas_nama,
                'tingkat_id' => $this->kelas_tingkat_id,
                'jurusan_id' => $this->kelas_jurusan_id ?: null,
            ]);
        }

        $this->showKelasModal = false;
        $this->reset(['editingKelasId', 'kelas_nama', 'kelas_tingkat_id', 'kelas_jurusan_id']);
        $this->dispatch('toast', type: 'success', message: 'Data kelas berhasil disimpan!');
    }

    public function confirmDeleteKelas(int $id): void
    {
        $this->dispatch('swal-confirm',
            title: 'Hapus Kelas?',
            text: 'Data kelas dan semua relasi siswanya akan terpengaruh.',
            confirmText: 'Ya, Hapus!',
            method: 'doDeleteKelas',
            payload: ['id' => $id]
        );
    }

    #[\Livewire\Attributes\On('doDeleteKelas')]
    public function doDeleteKelas(int $id): void
    {
        Kelas::destroy($id);
        $this->dispatch('toast', type: 'success', message: 'Kelas berhasil dihapus!');
    }

    // ── Student CRUD ──
    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'nisn', 'nama_lengkap', 'password']);
        $this->kelas_id = $this->selectedKelasId ?? 0;
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
        $this->reset(['editingId', 'nisn', 'nama_lengkap', 'password']);
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

    public function importExcel(): void
    {
        $this->validate([
            'excelFile' => 'required|file|max:10240',
        ], [
            'excelFile.required' => 'Pilih berkas Excel terlebih dahulu.',
        ]);

        if (!$this->selectedKelasId) return;

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

                    $nisn = trim($row[0]);
                    $nama = trim($row[1]);
                    $pass = !empty($row[2]) ? trim($row[2]) : '123456';

                    // Find or create user
                    $user = User::find($nisn);
                    if ($user) {
                        $user->update([
                            'nama_lengkap' => $nama,
                        ]);
                        $imported++;
                    } else {
                        $user = User::create([
                            'id' => $nisn,
                            'nama_lengkap' => $nama,
                            'password' => $pass,
                            'role' => 'siswa',
                        ]);
                        Siswa::create([
                            'user_id' => $user->id,
                            'kelas_id' => $this->selectedKelasId,
                        ]);
                        $imported++;
                    }
                }
                $this->dispatch('toast', type: 'success', message: "Berhasil mengimpor $imported siswa ke kelas ini! ($skipped baris dilewati)");
            } else {
                $this->dispatch('toast', type: 'error', message: 'File Excel tidak memiliki baris data.');
            }
        } else {
            $this->dispatch('toast', type: 'error', message: 'Gagal menguraikan file Excel: ' . \Shuchkin\SimpleXLSX::parseError());
        }

        $this->excelFile = null;
    }

    public function render()
    {
        $activeKelas = null;
        $siswaList = collect();

        if ($this->selectedKelasId) {
            $activeKelas = Kelas::with(['tingkat', 'jurusan'])->findOrFail($this->selectedKelasId);

            $query = Siswa::query()
                ->select('siswa.*')
                ->join('users', 'siswa.user_id', '=', 'users.id')
                ->where('siswa.kelas_id', $this->selectedKelasId)
                ->with(['user', 'kelas']);

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('users.nama_lengkap', 'like', "%{$this->search}%")
                      ->orWhere('users.id', 'like', "%{$this->search}%");
                });
            }

            if ($this->sortBy === 'nama_lengkap') {
                $query->orderBy('users.nama_lengkap', $this->sortDir);
            }

            // Tiebreaker: ensure deterministic pagination
            $query->orderBy('siswa.id', 'asc');

            $siswaList = $query->paginate(10);
        }

        // Get class list with student count
        $kelasList = Kelas::with(['tingkat', 'jurusan'])
            ->withCount('siswa')
            ->orderBy('tingkat_id')
            ->orderBy('nama')
            ->get();

        return view('livewire.admin.data-siswa', [
            'kelasList' => $kelasList,
            'activeKelas' => $activeKelas,
            'siswaList' => $siswaList,
            'tingkatList' => \App\Models\Tingkat::orderBy('kode')->get(),
            'jurusanList' => \App\Models\Jurusan::orderBy('kode')->get(),
        ]);
    }
}
