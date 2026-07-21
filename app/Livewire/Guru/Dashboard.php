<?php

namespace App\Livewire\Guru;

use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\JamPelajaran;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Dashboard Guru')]
class Dashboard extends Component
{
    use WithFileUploads;

    public string $activeTab = 'hari-ini';
    public string $selectedTahunAjaran = '';

    // Modul Ajar properties
    public $modulAjarFile;
    public ?int $selectedMapelId = null;
    public string $passwordConfirm = '';
    public bool $showUploadModal = false;

    // Delete properties
    public ?int $deletingModulAjarId = null;
    public string $deletePasswordConfirm = '';
    public bool $showDeleteModal = false;

    public function mount()
    {
        $this->selectedTahunAjaran = \App\Models\Pengaturan::activeTahunAjaran();
    }

    public function openUploadModal()
    {
        $user = auth()->user();
        if (empty($user->password)) {
            $this->dispatch('toast', type: 'error', message: 'Password Anda belum diatur. Silakan hubungi admin untuk mengisi password akun Anda agar dapat mengelola modul ajar.');
            return;
        }
        $this->resetUploadForm();
        $this->showUploadModal = true;
    }

    public function resetUploadForm()
    {
        $this->modulAjarFile = null;
        $this->selectedMapelId = null;
        $this->passwordConfirm = '';
        $this->resetValidation();
    }

    public function uploadModulAjar()
    {
        $user = auth()->user();
        if (empty($user->password)) {
            $this->dispatch('toast', type: 'error', message: 'Password Anda belum diatur. Silakan hubungi admin.');
            return;
        }

        $this->validate([
            'selectedMapelId' => 'required|exists:mapel,id',
            'modulAjarFile' => 'required|file|max:10240', // max 10MB
            'passwordConfirm' => 'required',
        ], [
            'selectedMapelId.required' => 'Pilih mata pelajaran.',
            'modulAjarFile.required' => 'Pilih file modul ajar.',
            'passwordConfirm.required' => 'Ketikkan password Anda.',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($this->passwordConfirm, $user->password)) {
            $this->addError('passwordConfirm', 'Password yang Anda masukkan salah.');
            return;
        }

        $guru = Guru::where('user_id', $user->id)->first();
        if (!$guru) return;

        $path = $this->modulAjarFile->store('modul_ajar', 'public');

        \App\Models\ModulAjar::create([
            'guru_id' => $guru->id,
            'mapel_id' => $this->selectedMapelId,
            'nama_file' => $this->modulAjarFile->getClientOriginalName(),
            'file_path' => $path,
        ]);

        $this->showUploadModal = false;
        $this->resetUploadForm();
        $this->dispatch('toast', type: 'success', message: 'Modul ajar berhasil diupload!');
    }

    public function confirmDeleteModulAjar($id)
    {
        $user = auth()->user();
        if (empty($user->password)) {
            $this->dispatch('toast', type: 'error', message: 'Password Anda belum diatur. Silakan hubungi admin.');
            return;
        }
        $this->deletingModulAjarId = $id;
        $this->deletePasswordConfirm = '';
        $this->showDeleteModal = true;
    }

    public function deleteModulAjar()
    {
        $user = auth()->user();
        $this->validate([
            'deletePasswordConfirm' => 'required',
        ], [
            'deletePasswordConfirm.required' => 'Password konfirmasi wajib diisi.',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($this->deletePasswordConfirm, $user->password)) {
            $this->addError('deletePasswordConfirm', 'Password salah.');
            return;
        }

        $ma = \App\Models\ModulAjar::find($this->deletingModulAjarId);
        if ($ma) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($ma->file_path);
            $ma->delete();
        }

        $this->showDeleteModal = false;
        $this->deletingModulAjarId = null;
        $this->deletePasswordConfirm = '';
        $this->dispatch('toast', type: 'success', message: 'Modul ajar berhasil dihapus!');
    }

    public function render()
    {
        $user = auth()->user();
        $guru = Guru::where('user_id', $user->id)->first();

        $guruMapels = $guru ? $guru->guruMapel()->with(['mapel', 'tingkat', 'jurusan'])->get() : collect();

        // Get unique mapel taught by this guru
        $uniqueMapels = collect();
        if ($guru) {
            $uniqueMapels = \App\Models\Mapel::whereIn('id', $guruMapels->pluck('mapel_id')->unique())->orderBy('nama')->get();
        }

        // Get schedule
        $hariMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $hariIni = $hariMap[Carbon::now()->format('l')] ?? 'Senin';

        $jadwalHariIni = $guru ? Jadwal::with(['mapel', 'kelas', 'jamPelajaran'])
            ->where('guru_id', $guru->id)
            ->where('tahun_ajaran', $this->selectedTahunAjaran)
            ->where('hari', $hariIni)
            ->get()
            ->sortBy(fn($j) => $j->jamPelajaran->jam_ke) : collect();

        $jadwalMingguan = $guru ? Jadwal::with(['mapel', 'kelas', 'jamPelajaran'])
            ->where('guru_id', $guru->id)
            ->where('tahun_ajaran', $this->selectedTahunAjaran)
            ->get()
            ->groupBy('hari') : collect();

        $modulAjars = $guru ? \App\Models\ModulAjar::with('mapel')
            ->where('guru_id', $guru->id)
            ->get() : collect();

        $allHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $jamList = JamPelajaran::where('hari', 'Senin')->orderBy('jam_mulai')->get();

        return view('livewire.guru.dashboard', [
            'guru' => $guru,
            'guruMapels' => $guruMapels,
            'uniqueMapels' => $uniqueMapels,
            'jadwalHariIni' => $jadwalHariIni,
            'jadwalMingguan' => $jadwalMingguan,
            'modulAjars' => $modulAjars,
            'hariIni' => $hariIni,
            'allHari' => $allHari,
            'jamList' => $jamList,
            'tahunAjaranList' => $this->getTahunAjaranList(),
        ]);
    }

    private function getTahunAjaranList(): array
    {
        $active = \App\Models\Pengaturan::activeTahunAjaran();
        $db = \App\Models\Jadwal::whereNotNull('tahun_ajaran')
            ->distinct()
            ->pluck('tahun_ajaran')
            ->toArray();
        if (!in_array($active, $db)) {
            $db[] = $active;
        }
        return array_unique($db);
    }
}
