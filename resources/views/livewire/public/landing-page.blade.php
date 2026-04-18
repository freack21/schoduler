<div>
    {{-- Navbar --}}
    <nav class="fixed top-0 left-0 right-0 z-50 bg-primary/95 backdrop-blur-md border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-secondary flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                    </div>
                    <span class="text-white font-bold text-sm">SMA Negeri 1 Tapung Hulu</span>
                </div>
                <a href="{{ route('login') }}" class="btn-secondary text-sm !py-2 !px-5">
                    Portal Login
                </a>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="relative min-h-[90vh] flex items-center bg-gradient-to-br from-primary via-primary-dark to-sidebar overflow-hidden">
        <div class="absolute inset-0">
            <div class="absolute top-20 left-10 w-72 h-72 bg-secondary/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-blue-500/5 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] border border-white/5 rounded-full"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] border border-white/3 rounded-full"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32">
            <div class="max-w-3xl">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-secondary/20 text-secondary text-sm font-medium mb-6">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
                    Sistem Penjadwalan Digital
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-tight mb-6">
                    SMA Negeri 1
                    <span class="text-secondary"> Tapung Hulu</span>
                </h1>
                <p class="text-lg text-gray-300 mb-8 leading-relaxed max-w-2xl">
                    Mewujudkan generasi unggul, berkarakter, dan berdaya saing tinggi melalui pendidikan berkualitas di Kabupaten Kampar, Riau.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('login') }}" class="btn-secondary !py-3 !px-8 text-base shadow-lg shadow-secondary/30 hover:shadow-xl hover:shadow-secondary/40 hover:-translate-y-0.5 transition-all duration-300">
                        Akses Portal Penjadwalan
                    </a>
                    <a href="#visi-misi" class="bg-white/10 backdrop-blur text-white px-8 py-3 rounded-lg hover:bg-white/20 transition-all duration-300 font-medium border border-white/20">
                        Pelajari Lebih Lanjut
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats Bar --}}
    <section class="bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary">500+</div>
                    <div class="text-sm text-gray-500 mt-1">Siswa Aktif</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary">40+</div>
                    <div class="text-sm text-gray-500 mt-1">Tenaga Pengajar</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary">18</div>
                    <div class="text-sm text-gray-500 mt-1">Ruang Kelas</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary">A</div>
                    <div class="text-sm text-gray-500 mt-1">Akreditasi</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Visi Misi --}}
    <section id="visi-misi" class="py-20 bg-bg-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <span class="inline-block px-4 py-1 rounded-full bg-primary/10 text-primary text-sm font-medium mb-3">Visi & Misi</span>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Visi & Misi Sekolah</h2>
            </div>
            <div class="grid md:grid-cols-2 gap-8">
                {{-- Visi --}}
                <div class="card hover:shadow-lg transition-shadow duration-300 border-t-4 border-t-primary">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                            <svg class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Visi</h3>
                    </div>
                    <p class="text-gray-600 leading-relaxed text-lg italic">
                        "Terwujudnya SMA Negeri 1 Tapung Hulu sebagai sekolah yang unggul dalam prestasi, berkarakter, berwawasan lingkungan, dan berdaya saing global berdasarkan iman dan taqwa."
                    </p>
                </div>
                {{-- Misi --}}
                <div class="card hover:shadow-lg transition-shadow duration-300 border-t-4 border-t-secondary">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-secondary/10 flex items-center justify-center">
                            <svg class="w-6 h-6 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Misi</h3>
                    </div>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-secondary mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Meningkatkan kualitas pembelajaran yang inovatif dan berbasis teknologi
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-secondary mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Membentuk karakter siswa yang berakhlak mulia dan berbudaya
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-secondary mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Mengembangkan potensi siswa di bidang akademik dan non-akademik
                        </li>
                        <li class="flex items-start gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-secondary mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Menciptakan lingkungan sekolah yang bersih, sehat, dan nyaman
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Sambutan Kepala Sekolah --}}
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="relative">
                        <div class="w-full aspect-[3/4] max-w-sm mx-auto rounded-2xl bg-gradient-to-br from-primary to-primary-dark flex items-end justify-center overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-t from-primary-dark/90 to-transparent"></div>
                            <svg class="w-48 h-48 text-white/20 mb-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        </div>
                        <div class="absolute -bottom-4 -right-4 w-24 h-24 bg-secondary/20 rounded-2xl -z-10"></div>
                        <div class="absolute -top-4 -left-4 w-16 h-16 bg-primary/10 rounded-xl -z-10"></div>
                    </div>
                </div>
                <div>
                    <span class="inline-block px-4 py-1 rounded-full bg-secondary/10 text-secondary text-sm font-medium mb-3">Sambutan</span>
                    <h2 class="text-3xl font-bold text-gray-900 mb-6">Sambutan Kepala Sekolah</h2>
                    <blockquote class="text-gray-600 leading-relaxed space-y-4 border-l-4 border-secondary pl-6">
                        <p>Assalamu'alaikum Warahmatullahi Wabarakatuh,</p>
                        <p>Puji syukur kita panjatkan kehadirat Allah SWT atas segala rahmat dan karunia-Nya. Dengan bangga kami mempersembahkan Sistem Penjadwalan Digital SMA Negeri 1 Tapung Hulu.</p>
                        <p>Melalui sistem ini, kami berkomitmen untuk meningkatkan efisiensi pengelolaan jadwal pelajaran demi terciptanya proses belajar mengajar yang lebih terstruktur dan optimal.</p>
                        <p>Kami mengajak seluruh civitas akademika untuk memanfaatkan sistem ini dengan sebaik-baiknya.</p>
                    </blockquote>
                    <div class="mt-6">
                        <p class="font-bold text-gray-900">Drs. H. Muhammad Nasir, M.Pd</p>
                        <p class="text-sm text-gray-500">Kepala SMA Negeri 1 Tapung Hulu</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Fasilitas --}}
    <section class="py-20 bg-bg-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <span class="inline-block px-4 py-1 rounded-full bg-primary/10 text-primary text-sm font-medium mb-3">Fasilitas</span>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Fasilitas Sekolah</h2>
                <p class="text-gray-500 mt-3 max-w-2xl mx-auto">Kami menyediakan berbagai fasilitas modern untuk mendukung kegiatan belajar mengajar yang berkualitas.</p>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @php
                    $fasilitas = [
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z"/>', 'title' => 'Ruang Kelas Modern', 'desc' => '18 ruang kelas ber-AC dengan proyektor dan whiteboard interaktif'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 16.5m14.8-1.2l.634-1.585a2.252 2.252 0 00-1.295-2.955A24.265 24.265 0 0012 9.75a24.265 24.265 0 00-7.139 1.06 2.252 2.252 0 00-1.295 2.955L5 15.3m0 0l-.634 1.586a2.25 2.25 0 001.295 2.956c2.312.788 4.758 1.158 7.239 1.158 2.481 0 4.927-.37 7.239-1.158a2.25 2.25 0 001.295-2.956L20.7 16.5"/>', 'title' => 'Laboratorium IPA', 'desc' => 'Lab Fisika, Kimia, dan Biologi dengan peralatan lengkap'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>', 'title' => 'Lab Komputer', 'desc' => '2 ruang lab komputer dengan internet berkecepatan tinggi'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>', 'title' => 'Perpustakaan', 'desc' => 'Koleksi 10.000+ buku dengan ruang baca yang nyaman dan tenang'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 8.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v8.25A2.25 2.25 0 006 16.5h2.25m8.25-8.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-7.5A2.25 2.25 0 018.25 18v-1.5m8.25-8.25h-6a2.25 2.25 0 00-2.25 2.25v6"/>', 'title' => 'Lapangan Olahraga', 'desc' => 'Lapangan basket, voli, futsal, dan lintasan atletik'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5l16.5-4.125M12 6.75c-2.708 0-5.363.224-7.948.655C2.999 7.58 2.25 8.507 2.25 9.574v9.176A2.25 2.25 0 004.5 21h15a2.25 2.25 0 002.25-2.25V9.574c0-1.067-.75-1.994-1.802-2.169A48.329 48.329 0 0012 6.75zm-1.683 6.443l-.005.005-.006-.005.006-.005.005.005zm-.005 2.127l-.005-.006.005-.005.005.005-.005.006zm-2.116-.006l-.005.006-.006-.006.005-.005.006.005zm-.005-2.116l-.006-.005.006-.005.005.005-.005.005zM9.255 10.5l.006-.005.005.005-.005.006-.006-.006zm.005 2.127l.005-.005.006.005-.006.005-.005-.005zm2.116-.006l.005.006-.005.005-.006-.005.006-.006zm.005-2.116l.006-.005.005.005-.005.006-.006-.006zM12 9.5l.006-.005.005.005-.005.006L12 9.5zm.005 2.127l.005-.006.006.006-.006.005-.005-.005zm2.116-.005l.005.005-.005.006-.006-.006.006-.005zm-.005-2.122l.006-.005.005.005-.005.006-.006-.006z"/>', 'title' => 'Masjid Sekolah', 'desc' => 'Masjid dengan kapasitas besar untuk ibadah dan kegiatan keagamaan'],
                    ];
                @endphp

                @foreach($fasilitas as $item)
                    <div class="card group hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                        <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary group-hover:scale-110 transition-all duration-300">
                            <svg class="w-6 h-6 text-primary group-hover:text-white transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">{!! $item['icon'] !!}</svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $item['title'] }}</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">{{ $item['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA + Footer --}}
    <footer class="bg-primary text-white">
        {{-- CTA --}}
        <div class="border-b border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
                <h2 class="text-3xl font-bold mb-4">Akses Portal Penjadwalan</h2>
                <p class="text-gray-300 mb-8 max-w-xl mx-auto">Masuk ke portal untuk melihat jadwal pelajaran, data guru, dan informasi akademik lainnya.</p>
                <a href="{{ route('login') }}" class="inline-flex items-center gap-3 bg-secondary hover:bg-secondary-light text-primary font-bold py-4 px-10 rounded-xl text-lg transition-all duration-300 shadow-lg shadow-secondary/30 hover:shadow-xl hover:shadow-secondary/40 hover:-translate-y-1">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    Akses Portal Penjadwalan
                </a>
            </div>
        </div>
        {{-- Footer Content --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 rounded-lg bg-secondary flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                        </div>
                        <span class="font-bold">SMA Negeri 1 Tapung Hulu</span>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed">Mewujudkan generasi unggul, berkarakter, dan berdaya saing tinggi.</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">Kontak</h4>
                    <div class="space-y-2 text-sm text-gray-400">
                        <p>Jl. Pendidikan No.1, Tapung Hulu</p>
                        <p>Kabupaten Kampar, Riau</p>
                        <p>Telp: (0762) 12345</p>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">Link Cepat</h4>
                    <div class="space-y-2 text-sm">
                        <a href="#visi-misi" class="block text-gray-400 hover:text-secondary transition-colors">Visi & Misi</a>
                        <a href="{{ route('login') }}" class="block text-gray-400 hover:text-secondary transition-colors">Portal Login</a>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10 mt-8 pt-8 text-center text-sm text-gray-500">
                &copy; {{ date('Y') }} SMA Negeri 1 Tapung Hulu. All rights reserved.
            </div>
        </div>
    </footer>
</div>
