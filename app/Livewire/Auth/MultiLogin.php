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

    public string $password = '';

    public string $errorMessage = '';

    public function authenticate(): void
    {
        $this->validate([
            'user_id' => 'required|string',
        ]);

        $user = \App\Models\User::find($this->user_id);

        if (!$user) {
            $this->dispatch('toast', type: 'error', message: 'NIP/NISN atau Username tidak ditemukan.');
            return;
        }

        if ($user->role !== 'admin') {
            $this->dispatch('toast', type: 'error', message: 'Halaman ini khusus Admin. Silakan login dari halaman utama.');
            return;
        }

        if (!Auth::attempt(['id' => $this->user_id, 'password' => $this->password])) {
            $this->dispatch('toast', type: 'error', message: 'Password salah untuk Admin.');
            return;
        }

        session()->regenerate();
        $this->redirect(route('admin.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.multi-login');
    }
}
