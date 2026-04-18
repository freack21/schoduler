<?php

namespace App\Livewire\PublicPage;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Beranda')]
class LandingPage extends Component
{
    public function render()
    {
        return view('livewire.public.landing-page');
    }
}
