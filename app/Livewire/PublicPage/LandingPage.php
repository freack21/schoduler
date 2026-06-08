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

    public function render()
    {
        return view('livewire.public.landing-page');
    }
}
