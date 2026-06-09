<?php

namespace App\Livewire\PublicPage;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Beranda')]
class LandingPage extends Component
{
    public string $userId = '';
    
    // Admin login fields
    public string $adminUsername = '';
    public string $adminPassword = '';
    
    public string $loginTab = 'siswa_guru'; // 'siswa_guru' or 'admin'

    public function loginSiswaGuru()
    {
        $this->validate([
            'userId' => 'required|string',
        ], [
            'userId.required' => 'NISN atau NIP harus diisi.',
        ]);

        $user = \App\Models\User::find($this->userId);

        if (!$user) {
            $this->dispatch('toast', type: 'error', message: 'NISN/NIP tidak ditemukan.');
            return;
        }

        if ($user->role === 'admin') {
            $this->dispatch('toast', type: 'error', message: 'Admin tidak dapat login dari halaman ini.');
            return;
        }

        // Login as Guru or Siswa without password
        \Illuminate\Support\Facades\Auth::login($user);
        session()->regenerate();

        if ($user->role === 'guru') {
            $this->redirect(route('guru.dashboard'), navigate: true);
        } else {
            $this->redirect(route('siswa.dashboard'), navigate: true);
        }
    }

    public function loginAdmin()
    {
        $this->validate([
            'adminUsername' => 'required|string',
            'adminPassword' => 'required|string',
        ], [
            'adminUsername.required' => 'Username harus diisi.',
            'adminPassword.required' => 'Password harus diisi.',
        ]);

        $user = \App\Models\User::where('id', $this->adminUsername)->first();
        
        if (!$user || $user->role !== 'admin' || !\Illuminate\Support\Facades\Hash::check($this->adminPassword, $user->password)) {
            $this->dispatch('toast', type: 'error', message: 'Username atau password salah.');
            return;
        }

        \Illuminate\Support\Facades\Auth::login($user);
        session()->regenerate();

        $this->redirect(route('admin.dashboard'), navigate: true);
    }

    public function render()
    {
        $siswaCount = \App\Models\Siswa::count();
        $guruCount = \App\Models\Guru::count();
        $kelasCount = \App\Models\Kelas::count();
        
        $kepsekNama = 'Drs. H. Muhammad Nasir, M.Pd';
        $allJsonPath = database_path('seeders/data/all.json');
        if (file_exists($allJsonPath)) {
            $jsonData = json_decode(file_get_contents($allJsonPath), true);
            if (isset($jsonData['guru'])) {
                foreach ($jsonData['guru'] as $g) {
                    if (isset($g['mapel']) && str_contains(strtolower($g['mapel']), 'kepala sekolah')) {
                        $kepsekNama = $g['nama'];
                        break;
                    }
                }
            }
        }

        return view('livewire.public.landing-page', [
            'siswaCount' => $siswaCount,
            'guruCount' => $guruCount,
            'kelasCount' => $kelasCount,
            'kepsekNama' => $kepsekNama,
        ]);
    }
}
