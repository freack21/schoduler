<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Login')]
class MultiLogin extends Component
{
    #[Validate('required|string')]
    public string $user_id = '';

    #[Validate('required|string|min:6')]
    public string $password = '';

    public string $errorMessage = '';

    public function authenticate(): void
    {
        $this->validate();

        if (Auth::attempt(['id' => $this->user_id, 'password' => $this->password])) {
            session()->regenerate();

            $user = Auth::user();

            match ($user->role) {
                'admin' => $this->redirect(route('admin.dashboard'), navigate: true),
                'guru' => $this->redirect(route('guru.dashboard'), navigate: true),
                'siswa' => $this->redirect(route('siswa.dashboard'), navigate: true),
            };
        } else {
            $this->dispatch('toast', type: 'error', message: 'ID atau password salah. Silakan coba lagi.');
        }
    }

    public function render()
    {
        return view('livewire.auth.multi-login');
    }
}
