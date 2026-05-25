<style>
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInRight {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}
</style>
<div>
    {{-- Navbar --}}
    <nav x-data="{ scrolled: false }" 
         @scroll.window="scrolled = (window.pageYOffset > 20)" 
         :class="{ 'bg-primary/95 backdrop-blur-xl shadow-lg border-b border-white/10': scrolled, 'bg-transparent border-transparent': !scrolled }"
         class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20 transition-all duration-300" :class="{ 'h-16': scrolled }">
                <div class="flex items-center gap-3 group cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-secondary flex items-center justify-center transform group-hover:rotate-12 transition-all duration-300 shadow-lg shadow-secondary/30">
                        <svg class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                    </div>
                    <span class="text-white font-bold text-lg tracking-wide">SMAN 1<span class="text-secondary"> Tapung Hulu</span></span>
                </div>
                <div class="flex items-center gap-6">
                    <div class="hidden md:flex space-x-8 text-white/80 font-medium text-sm">
                        <a href="#visi-misi" class="hover:text-secondary transition-colors">Visi & Misi</a>
                        <a href="#fasilitas" class="hover:text-secondary transition-colors">Fasilitas</a>
                    </div>
                    <a href="{{ route('login') }}" class="group relative px-6 py-2.5 font-bold text-primary bg-secondary rounded-full overflow-hidden shadow-lg shadow-secondary/40 hover:shadow-secondary/60 hover:-translate-y-0.5 transition-all duration-300">
                        <div class="absolute inset-0 w-full h-full bg-white/20 transform -translate-x-full group-hover:translate-x-full transition-transform duration-500 ease-out"></div>
                        <span class="relative flex items-center gap-2 text-sm">
                            Portal Login
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden bg-primary">
        {{-- Modern Animated Background --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[25%] -left-[10%] w-[50%] h-[50%] rounded-full bg-secondary/20 blur-[120px] animate-pulse"></div>
            <div class="absolute top-[20%] -right-[10%] w-[40%] h-[60%] rounded-full bg-blue-500/10 blur-[100px] mix-blend-overlay"></div>
            <div class="absolute -bottom-[20%] left-[20%] w-[60%] h-[50%] rounded-full bg-primary-dark/50 blur-[100px]"></div>
            
            {{-- Grid Pattern overlay --}}
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCI+PGFwcGVuZD48cmVjdCB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIGZpbGw9Im5vbmUiLz48cGF0aCBkPSJNMCAwaDQwdjQwSDB6IiBmaWxsPSJub25lIi8+PHBhdGggZD0iTTAgNDBMMCAwTDAgMCIgc3Ryb2tlPSJyZ2JhKDI1NSwyNTUsMjU1LDAuMDUpIiBzdHJva2Utd2lkdGg9IjEiLz48cGF0aCBkPSJNNDAgMEwwIDBMMCAwIiBzdHJva2U9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiIHN0cm9rZS13aWR0aD0iMSIvPjwvc3ZnPg==')] opacity-30"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-8 items-center">
                {{-- Left Content --}}
                <div class="text-center lg:text-left z-10">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10 backdrop-blur-sm text-secondary text-sm font-semibold mb-8 animate-[fadeInDown_1s_ease-out]">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-secondary opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-secondary"></span>
                        </span>
                        Sistem Penjadwalan Digital 2.0
                    </div>
                    
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight mb-6 animate-[fadeInUp_1s_ease-out]">
                        Membangun Masa Depan di
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-secondary to-yellow-200">
                            SMAN 1 Tapung Hulu
                        </span>
                    </h1>
                    
                    <p class="text-lg sm:text-xl text-gray-300 mb-10 leading-relaxed max-w-2xl mx-auto lg:mx-0 animate-[fadeInUp_1.2s_ease-out]">
                        Mewujudkan generasi unggul, berkarakter, dan berdaya saing tinggi melalui pendidikan berkualitas berbasis teknologi di Kabupaten Kampar.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 animate-[fadeInUp_1.4s_ease-out]">
                        <a href="{{ route('login') }}" class="w-full sm:w-auto text-center px-8 py-4 rounded-xl bg-secondary text-primary font-bold text-lg hover:bg-yellow-400 hover:shadow-xl hover:shadow-secondary/30 hover:-translate-y-1 transition-all duration-300 flex items-center justify-center gap-2">
                            Akses Portal
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                        <a href="#visi-misi" class="w-full sm:w-auto text-center px-8 py-4 rounded-xl bg-white/5 hover:bg-white/10 text-white font-medium text-lg border border-white/10 hover:border-white/20 backdrop-blur-sm transition-all duration-300">
                            Pelajari Lebih Lanjut
                        </a>
                    </div>
                </div>

                {{-- Right Hero Image/Mockup --}}
                <div class="relative hidden lg:block z-10 animate-[fadeInRight_1.2s_ease-out]">
                    <div class="absolute inset-0 bg-gradient-to-tr from-secondary/20 to-transparent rounded-3xl blur-2xl transform rotate-3"></div>
                    <div class="relative rounded-3xl overflow-hidden border border-white/10 bg-white/5 backdrop-blur-sm shadow-2xl">
                        <div class="h-10 bg-black/40 flex items-center px-4 border-b border-white/10 gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-400/80"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400/80"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400/80"></div>
                        </div>
                        <img src="https://images.unsplash.com/photo-1577896851231-70ef18881754?q=80&w=1000&auto=format&fit=crop" alt="Dashboard Preview" class="w-full h-auto object-cover opacity-90 mix-blend-luminosity hover:mix-blend-normal transition-all duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-primary via-transparent to-transparent opacity-60"></div>
                    </div>
                    
                    {{-- Floating Badge --}}
                    <div class="absolute -bottom-6 -left-10 bg-white p-4 rounded-2xl shadow-xl shadow-black/10 flex items-center gap-4 animate-bounce hover:scale-105 transition-transform" style="animation-duration: 3s;">
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Status Jadwal</p>
                            <p class="font-bold text-gray-900">Aktif & Optimal</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Overlapping Stats Bar --}}
    <section class="relative z-20 -mt-10 mb-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-2xl shadow-gray-200/50 p-8 border border-gray-100">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 divide-x divide-gray-100">
                <div class="text-center px-4 group">
                    <div class="text-4xl font-extrabold text-primary mb-1 group-hover:scale-110 transition-transform duration-300">500+</div>
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Siswa Aktif</div>
                </div>
                <div class="text-center px-4 group">
                    <div class="text-4xl font-extrabold text-primary mb-1 group-hover:scale-110 transition-transform duration-300">40+</div>
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Tenaga Pengajar</div>
                </div>
                <div class="text-center px-4 group">
                    <div class="text-4xl font-extrabold text-primary mb-1 group-hover:scale-110 transition-transform duration-300">18</div>
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Ruang Kelas</div>
                </div>
                <div class="text-center px-4 group">
                    <div class="text-4xl font-extrabold text-primary mb-1 group-hover:scale-110 transition-transform duration-300 text-secondary">A</div>
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Akreditasi</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Visi Misi Section --}}
    <section id="visi-misi" class="py-20 bg-bg-light relative overflow-hidden">
        <div class="absolute top-0 left-0 w-64 h-64 bg-secondary/5 rounded-full blur-3xl"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16">
                <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-sm font-bold tracking-wide mb-4">LANDASAN KAMI</span>
                <h2 class="text-3xl sm:text-5xl font-extrabold text-gray-900 tracking-tight">Visi & Misi Sekolah</h2>
            </div>
            
            <div class="grid lg:grid-cols-2 gap-10">
                {{-- Visi Card --}}
                <div class="bg-white rounded-3xl p-10 shadow-lg shadow-gray-200/50 border border-gray-100 hover:shadow-2xl hover:shadow-primary/10 hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-full transition-transform group-hover:scale-150 duration-500"></div>
                    <div class="flex items-center gap-4 mb-6 relative z-10">
                        <div class="w-14 h-14 rounded-2xl bg-primary flex items-center justify-center shadow-lg shadow-primary/30">
                            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h3 class="text-3xl font-extrabold text-gray-900">Visi</h3>
                    </div>
                    <p class="text-gray-600 leading-loose text-xl font-medium relative z-10">
                        <span class="text-primary text-4xl leading-none absolute -top-4 -left-3 opacity-20">"</span>
                        Terwujudnya SMA Negeri 1 Tapung Hulu sebagai sekolah yang unggul dalam prestasi, berkarakter, berwawasan lingkungan, dan berdaya saing global berdasarkan iman dan taqwa.
                        <span class="text-primary text-4xl leading-none absolute -bottom-6 opacity-20">"</span>
                    </p>
                </div>
                
                {{-- Misi Card --}}
                <div class="bg-white rounded-3xl p-10 shadow-lg shadow-gray-200/50 border border-gray-100 hover:shadow-2xl hover:shadow-secondary/10 hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-secondary/10 rounded-bl-full transition-transform group-hover:scale-150 duration-500"></div>
                    <div class="flex items-center gap-4 mb-8 relative z-10">
                        <div class="w-14 h-14 rounded-2xl bg-secondary flex items-center justify-center shadow-lg shadow-secondary/30">
                            <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                        </div>
                        <h3 class="text-3xl font-extrabold text-gray-900">Misi</h3>
                    </div>
                    <ul class="space-y-5 relative z-10">
                        @foreach([
                            'Meningkatkan kualitas pembelajaran yang inovatif dan berbasis teknologi',
                            'Membentuk karakter siswa yang berakhlak mulia dan berbudaya',
                            'Mengembangkan potensi siswa di bidang akademik dan non-akademik',
                            'Menciptakan lingkungan sekolah yang bersih, sehat, dan nyaman'
                        ] as $misi)
                        <li class="flex items-start gap-4 p-4 rounded-xl hover:bg-gray-50 transition-colors">
                            <div class="w-6 h-6 rounded-full bg-secondary/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-secondary font-bold" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-gray-700 font-medium text-lg">{{ $misi }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Sambutan Kepala Sekolah --}}
    <section class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="relative order-2 lg:order-1">
                    <div class="relative w-full max-w-md mx-auto aspect-[4/5] rounded-3xl bg-gray-100 overflow-hidden shadow-2xl">
                        <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?q=80&w=800&auto=format&fit=crop" alt="Kepala Sekolah" class="w-full h-full object-cover transition-transform duration-700 hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-primary/80 via-transparent to-transparent opacity-80"></div>
                        <div class="absolute bottom-6 left-6 right-6">
                            <p class="text-white font-bold text-2xl mb-1">Drs. H. Muhammad Nasir, M.Pd</p>
                            <p class="text-secondary font-medium">Kepala SMA Negeri 1 Tapung Hulu</p>
                        </div>
                    </div>
                    {{-- Decorative Elements --}}
                    <div class="absolute -bottom-8 -right-8 w-32 h-32 bg-secondary/20 rounded-3xl -z-10 animate-pulse" style="animation-duration: 4s;"></div>
                    <div class="absolute -top-8 -left-8 w-24 h-24 bg-primary/10 rounded-full -z-10"></div>
                    
                    {{-- Floating Quote Mark --}}
                    <div class="absolute -right-6 top-1/4 w-16 h-16 bg-white rounded-full shadow-xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-primary" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
                    </div>
                </div>
                
                <div class="order-1 lg:order-2">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-secondary/10 text-secondary text-sm font-bold tracking-wide mb-4">SAMBUTAN PIMPINAN</span>
                    <h2 class="text-3xl sm:text-5xl font-extrabold text-gray-900 mb-8 leading-tight">Membangun Karakter Melalui Pendidikan Modern</h2>
                    
                    <div class="space-y-6 text-gray-600 text-lg leading-relaxed mb-10">
                        <p class="font-medium text-gray-900">Assalamu'alaikum Warahmatullahi Wabarakatuh,</p>
                        <p>Puji syukur kita panjatkan kehadirat Allah SWT atas segala rahmat dan karunia-Nya. Dengan bangga kami mempersembahkan Sistem Penjadwalan Digital SMA Negeri 1 Tapung Hulu.</p>
                        <p>Melalui sistem penjadwalan cerdas berbasis kecerdasan buatan ini, kami berkomitmen untuk meningkatkan efisiensi tata kelola sekolah demi terciptanya proses belajar mengajar yang lebih terstruktur, optimal, dan tanpa bentrok.</p>
                        <p>Kami mengajak seluruh civitas akademika untuk terus bersinergi dalam memajukan sekolah kita tercinta.</p>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?q=80&w=150&auto=format&fit=crop" alt="Signature" class="w-16 h-16 rounded-full object-cover shadow-md border-2 border-white lg:hidden">
                        <div>
                            <p class="font-extrabold text-gray-900">M. Nasir</p>
                            <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Kepala Sekolah</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Fasilitas --}}
    <section id="fasilitas" class="py-24 bg-gray-50 border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 max-w-3xl mx-auto">
                <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-sm font-bold tracking-wide mb-4">INFRASTRUKTUR</span>
                <h2 class="text-3xl sm:text-5xl font-extrabold text-gray-900 mb-6">Fasilitas Pendukung</h2>
                <p class="text-lg text-gray-500">Kami berinvestasi pada fasilitas terbaik untuk memberikan kenyamanan dan pengalaman belajar maksimal bagi seluruh siswa.</p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @php
                    $fasilitas = [
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z"/>', 'title' => 'Ruang Kelas Pintar', 'desc' => '18 ruang kelas ber-AC dilengkapi proyektor digital dan konektivitas WiFi.'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>', 'title' => 'Laboratorium Terpadu', 'desc' => 'Laboratorium Fisika, Kimia, dan Biologi berstandar nasional untuk praktikum.'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>', 'title' => 'Lab Komputer Modern', 'desc' => 'Ruang TIK dengan spesifikasi komputer terbaru untuk literasi digital.'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>', 'title' => 'Perpustakaan Digital', 'desc' => 'Koleksi puluhan ribu buku fisik dan e-book dengan sistem peminjaman modern.'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 8.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v8.25A2.25 2.25 0 006 16.5h2.25m8.25-8.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-7.5A2.25 2.25 0 018.25 18v-1.5m8.25-8.25h-6a2.25 2.25 0 00-2.25 2.25v6"/>', 'title' => 'Kompleks Olahraga', 'desc' => 'Area olahraga komprehensif mencakup basket, voli, futsal, dan atletik.'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>', 'title' => 'Masjid Sekolah', 'desc' => 'Pusat kegiatan kerohanian yang nyaman dan representatif.'],
                    ];
                @endphp

                @foreach($fasilitas as $item)
                    <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-lg shadow-gray-200/40 hover:shadow-2xl hover:shadow-primary/20 hover:-translate-y-2 hover:border-primary/20 transition-all duration-500 group relative overflow-hidden">
                        {{-- Hover Gradient Background --}}
                        <div class="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        
                        <div class="relative z-10">
                            <div class="w-16 h-16 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center mb-6 group-hover:bg-primary group-hover:border-primary group-hover:scale-110 group-hover:rotate-6 transition-all duration-500">
                                <svg class="w-8 h-8 text-primary group-hover:text-white transition-colors duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">{!! $item['icon'] !!}</svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">{{ $item['title'] }}</h3>
                            <p class="text-gray-500 leading-relaxed">{{ $item['desc'] }}</p>
                        </div>
                        
                        {{-- Bottom decorative line --}}
                        <div class="absolute bottom-0 left-0 h-1 w-0 bg-primary group-hover:w-full transition-all duration-500"></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA Full Banner --}}
    <section class="relative bg-primary overflow-hidden">
        <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=1200&auto=format&fit=crop')] bg-cover bg-center opacity-10 mix-blend-overlay"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-primary via-primary/90 to-transparent"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 relative z-10">
            <div class="max-w-3xl">
                <h2 class="text-4xl sm:text-5xl font-extrabold text-white mb-6">Siap Mengeksplorasi Portal Akademik?</h2>
                <p class="text-xl text-gray-300 mb-10 leading-relaxed">Kelola jadwal, pantau presensi, dan optimalkan proses belajar mengajar dengan satu platform terpusat.</p>
                <a href="{{ route('login') }}" class="inline-flex items-center gap-3 bg-secondary hover:bg-yellow-400 text-primary font-extrabold py-5 px-12 rounded-full text-lg transition-all duration-300 shadow-xl shadow-secondary/20 hover:shadow-2xl hover:shadow-secondary/40 hover:-translate-y-1">
                    Masuk Sekarang
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </div>
    </section>

    {{-- Minimalist Footer --}}
    <footer class="bg-primary-dark text-white border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-12 lg:gap-8">
                <div class="md:col-span-5">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-secondary flex items-center justify-center">
                            <svg class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                        </div>
                        <div>
                            <span class="block font-bold text-xl tracking-wide">SMAN 1</span>
                            <span class="block text-secondary font-medium -mt-1">Tapung Hulu</span>
                        </div>
                    </div>
                    <p class="text-gray-400 leading-relaxed pr-8">
                        Portal Akademik Digital untuk mewujudkan ekosistem pendidikan modern yang efisien dan transparan.
                    </p>
                </div>
                
                <div class="md:col-span-4">
                    <h4 class="text-lg font-bold text-white mb-6 uppercase tracking-wider">Hubungi Kami</h4>
                    <ul class="space-y-4 text-gray-400">
                        <li class="flex items-start gap-3 hover:text-white transition-colors">
                            <svg class="w-6 h-6 flex-shrink-0 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                            <span>Jl. Pendidikan No.1, Kec. Tapung Hulu, Kabupaten Kampar, Riau 28464</span>
                        </li>
                        <li class="flex items-center gap-3 hover:text-white transition-colors">
                            <svg class="w-5 h-5 flex-shrink-0 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.864-1.041l-3.286-.481c-.526-.076-1.033.2-1.248.712l-.744 1.745c-2.908-1.42-5.22-3.732-6.64-6.64l1.745-.744c.512-.215.788-.722.712-1.248l-.481-3.286c-.075-.512-.525-.864-1.041-.864H4.5A2.25 2.25 0 002.25 6.75z"/></svg>
                            <span>(0762) 1234567</span>
                        </li>
                    </ul>
                </div>
                
                <div class="md:col-span-3">
                    <h4 class="text-lg font-bold text-white mb-6 uppercase tracking-wider">Navigasi</h4>
                    <ul class="space-y-3">
                        <li><a href="#visi-misi" class="text-gray-400 hover:text-secondary hover:pl-2 transition-all duration-300 block">Visi & Misi</a></li>
                        <li><a href="#fasilitas" class="text-gray-400 hover:text-secondary hover:pl-2 transition-all duration-300 block">Fasilitas</a></li>
                        <li><a href="{{ route('login') }}" class="text-gray-400 hover:text-secondary hover:pl-2 transition-all duration-300 block">Portal Akademik</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-white/10 mt-16 pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-gray-500">
                <p>&copy; {{ date('Y') }} SMA Negeri 1 Tapung Hulu. All rights reserved.</p>
                <div class="flex gap-4">
                    <a href="#" class="hover:text-white transition-colors">Kebijakan Privasi</a>
                    <a href="#" class="hover:text-white transition-colors">Syarat & Ketentuan</a>
                </div>
            </div>
        </div>
    </footer>
</div>
