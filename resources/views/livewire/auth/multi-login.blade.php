<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary via-primary-dark to-sidebar px-4 py-8">
    {{-- Decorative background elements --}}
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-secondary/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-secondary/5 rounded-full blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-md">
        {{-- Logo / Brand --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-secondary shadow-lg shadow-secondary/30 mb-4">
                <svg class="w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">Portal Admin</h1>
            <p class="text-gray-400 mt-1 text-sm">Sistem Penjadwalan SMAN 1 Tapung Hulu</p>
        </div>

        {{-- Login Card --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-8 border border-white/20 shadow-2xl">
            <h2 class="text-lg font-semibold text-white mb-6 text-center">Masuk ke Akun Anda</h2>


            <form wire:submit="authenticate" class="space-y-5">
                {{-- User ID --}}
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-300 mb-1.5">Username Admin</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        </div>
                        <input wire:model="user_id" type="text" id="user_id" placeholder="Masukkan Username Anda" class="w-full bg-white/10 border border-white/20 rounded-lg pl-10 pr-4 py-2.5 text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-secondary/50 focus:border-secondary/50 outline-none transition-all duration-200" autofocus>
                    </div>
                    @error('user_id') <p class="mt-1 text-xs text-red-300">{{ $message }}</p> @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-1.5">
                        Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                        </div>
                        <input wire:model="password" type="password" id="password" placeholder="Masukkan Password Anda" class="w-full bg-white/10 border border-white/20 rounded-lg pl-10 pr-4 py-2.5 text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-secondary/50 focus:border-secondary/50 outline-none transition-all duration-200">
                    </div>
                    @error('password') <p class="mt-1 text-xs text-red-300">{{ $message }}</p> @enderror
                </div>

                {{-- Submit --}}
                <button type="submit" class="w-full bg-secondary hover:bg-secondary-light text-primary font-semibold py-2.5 rounded-lg transition-all duration-200 flex items-center justify-center gap-2 shadow-lg shadow-secondary/20 cursor-pointer">
                    <span wire:loading.remove wire:target="authenticate">Masuk</span>
                    <svg wire:loading wire:target="authenticate" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('home') }}" class="text-sm text-gray-400 hover:text-secondary transition-colors">
                    ← Kembali ke Halaman Utama
                </a>
            </div>
        </div>

        {{-- Info --}}
        <p class="text-center text-xs text-gray-500 mt-6">
            Halaman ini dikhususkan untuk Administrator Sistem.
        </p>
    </div>
</div>
