<div>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* Custom additional styles if needed */
    </style>
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
                <div class="text-center lg:text-left z-10" data-aos="fade-right" data-aos-duration="1000">
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight mb-6" data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
                        Membangun Masa Depan di
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-secondary to-yellow-200">
                            SMAN 1 Tapung Hulu
                        </span>
                    </h1>
                    
                    <p class="text-lg sm:text-xl text-gray-300 mb-10 leading-relaxed max-w-2xl mx-auto lg:mx-0" data-aos="fade-up" data-aos-delay="400" data-aos-duration="1000">
                        Berdiri sejak tahun 2003, SMA Negeri 1 Tapung Hulu berkomitmen untuk terus meningkatkan kualitas pendidikan dan melahirkan generasi muda yang unggul, berakhlak mulia, dan siap menghadapi tantangan masa depan.
                    </p>
                </div>

                {{-- Right Hero Image/Mockup --}}
                <div class="relative hidden lg:block z-10" data-aos="fade-left" data-aos-duration="1200" data-aos-delay="400">
                    <div class="absolute inset-0 bg-gradient-to-tr from-secondary/20 to-transparent rounded-3xl blur-2xl transform rotate-3"></div>
                    <div class="relative rounded-3xl overflow-hidden border border-white/10 bg-white/5 backdrop-blur-sm shadow-2xl">
                        <div class="h-10 bg-black/40 flex items-center px-4 border-b border-white/10 gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-400/80"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400/80"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400/80"></div>
                        </div>
                        <img src="{{ asset('image/foto1.jpg') }}" alt="Sekolah" class="w-full h-auto object-cover opacity-90 transition-all duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-primary via-transparent to-transparent opacity-60"></div>
                    </div>
                    
                    {{-- Floating Badge --}}
                    <!-- <div class="absolute -bottom-6 -left-10 bg-white p-4 rounded-2xl shadow-xl shadow-black/10 flex items-center gap-4 animate-bounce hover:scale-105 transition-transform" style="animation-duration: 3s;">
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Status Jadwal</p>
                            <p class="font-bold text-gray-900">Aktif & Optimal</p>
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
    </section>

    {{-- Overlapping Stats Bar --}}
    <section class="relative z-20 -mt-10 mb-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-2xl shadow-gray-200/50 p-8 border border-gray-100">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 divide-x divide-gray-100">
                <div class="text-center px-4 group">
                    <div class="text-4xl font-extrabold text-primary mb-1 group-hover:scale-110 transition-transform duration-300">{{ $siswaCount }}</div>
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Siswa Aktif</div>
                </div>
                <div class="text-center px-4 group">
                    <div class="text-4xl font-extrabold text-primary mb-1 group-hover:scale-110 transition-transform duration-300">{{ $guruCount }}</div>
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Tenaga Pengajar</div>
                </div>
                <div class="text-center px-4 group" data-aos="zoom-in" data-aos-delay="200">
                    <div class="text-4xl font-extrabold text-primary mb-1 group-hover:scale-110 transition-transform duration-300">{{ $kelasCount }}</div>
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Rombongan Belajar</div>
                </div>
                <div class="text-center px-4 group">
                    <div class="text-4xl font-extrabold text-primary mb-1 group-hover:scale-110 transition-transform duration-300 text-secondary">A</div>
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Akreditasi</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Tentang Kami Section --}}
    <section id="tentang-kami" class="py-20 bg-white relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16">
                <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-sm font-bold tracking-wide mb-4">PROFIL SEKOLAH</span>
                <h2 class="text-3xl sm:text-5xl font-extrabold text-gray-900 tracking-tight">SMA Negeri 1 Tapung Hulu</h2>
            </div>
            <div class="grid lg:grid-cols-2 gap-10 items-center">
                <div data-aos="fade-right" class="space-y-6 text-gray-600 leading-relaxed text-lg text-justify">
                    <p>SMA Negeri 1 Tapung Hulu, yang terletak di Jl. Kampung Lama No. 10, KASIKAN, KEC. TAPUNG HULU, KAB. KAMPAR, PROV. RIAU, merupakan sekolah menengah atas negeri yang memiliki reputasi baik di wilayahnya. Dengan NPSN 10494916, sekolah ini telah berdiri sejak tahun 2003 berdasarkan SK Pendirian No. 010/SK/KACAB/2002 yang dikeluarkan pada tanggal 07-01-2003.</p>
                    <p>Sekolah ini memiliki luas tanah yang cukup luas, mencapai 20.000 meter persegi. Hal ini memungkinkan sekolah untuk memiliki berbagai fasilitas yang mendukung proses belajar mengajar, seperti ruang kelas yang nyaman, laboratorium, perpustakaan yang lengkap, serta lapangan olahraga yang memadai. Sekolah juga dilengkapi dengan akses internet dan listrik dari PLN yang menjamin kelancaran kegiatan belajar mengajar.</p>
                    <p>Dalam hal prestasi, SMA Negeri 1 Tapung Hulu telah menorehkan berbagai prestasi baik di tingkat regional maupun nasional. Hal ini ditunjukkan dengan raihan akreditasi A yang diperoleh sekolah berdasarkan SK No. 1449/BAN-SM/SK/2019. Kami berkomitmen untuk terus meningkatkan kualitas pendidikan dan melahirkan generasi muda yang unggul, berakhlak mulia, dan siap menghadapi tantangan masa depan.</p>
                </div>
                <div data-aos="fade-left" class="relative">
                    <div class="absolute inset-0 bg-gradient-to-tr from-secondary/20 to-transparent rounded-3xl blur-2xl transform -rotate-3"></div>
                    <div class="relative rounded-3xl overflow-hidden border border-gray-100 shadow-2xl">
                        <img src="{{ asset('image/foto2.jpg') }}" alt="Kegiatan Sekolah" class="w-full h-auto object-cover hover:scale-105 transition-transform duration-700">
                    </div>
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
                <div data-aos="fade-up" class="bg-white rounded-3xl p-10 shadow-lg shadow-gray-200/50 border border-gray-100 hover:shadow-2xl hover:shadow-primary/10 hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-full transition-transform group-hover:scale-150 duration-500"></div>
                    <div class="flex items-center gap-4 mb-6 relative z-10">
                        <div class="w-14 h-14 rounded-2xl bg-primary flex items-center justify-center shadow-lg shadow-primary/30">
                            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h3 class="text-3xl font-extrabold text-gray-900">Visi</h3>
                    </div>
                    <p class="text-gray-600 leading-loose text-xl font-medium relative z-10">
                        <span class="text-primary text-4xl leading-none absolute -top-4 -left-3 opacity-20">"</span>
                        Terwujudnya SMA Negeri 1 Tapung Hulu sebagai pelita pendidikan di pedesaan yang menghasilkan generasi berprestasi, mandiri, berakar pada kearifan lokal, dan berwawasan lingkungan global berdasarkan iman dan taqwa.
                        <span class="text-primary text-4xl leading-none absolute -bottom-6 opacity-20">"</span>
                    </p>
                </div>
                
                {{-- Misi Card --}}
                <div data-aos="fade-up" data-aos-delay="200" class="bg-white rounded-3xl p-10 shadow-lg shadow-gray-200/50 border border-gray-100 hover:shadow-2xl hover:shadow-secondary/10 hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-secondary/10 rounded-bl-full transition-transform group-hover:scale-150 duration-500"></div>
                    <div class="flex items-center gap-4 mb-8 relative z-10">
                        <div class="w-14 h-14 rounded-2xl bg-secondary flex items-center justify-center shadow-lg shadow-secondary/30">
                            <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                        </div>
                        <h3 class="text-3xl font-extrabold text-gray-900">Misi</h3>
                    </div>
                    <ul class="space-y-5 relative z-10">
                        @foreach([
                            'Meningkatkan kualitas pembelajaran inovatif yang relevan dengan potensi sumber daya alam dan kearifan lokal desa.',
                            'Membentuk karakter santun, bergotong-royong, dan berakhlak mulia sesuai nilai luhur masyarakat pedesaan.',
                            'Membekali siswa dengan keterampilan terapan dan pemanfaatan teknologi digital untuk memajukan kesejahteraan lingkungan sekitar.',
                            'Menciptakan lingkungan sekolah yang asri, menyatu dengan alam, dan berwawasan agrikultur.'
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
                <div class="relative order-2 lg:order-1" data-aos="fade-right">
                    <div class="relative w-full max-w-md mx-auto aspect-[4/5] rounded-3xl bg-gray-100 overflow-hidden shadow-2xl">
                        <img src="{{ asset('image/foto3.jpg') }}" alt="Kepala Sekolah" class="w-full h-full object-cover transition-transform duration-700 hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-primary/80 via-transparent to-transparent opacity-80"></div>
                        <div class="absolute bottom-6 left-6 right-6">
                            <p class="text-white font-bold text-2xl mb-1">{{ $kepsekNama }}</p>
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
                
                <div class="order-1 lg:order-2" data-aos="fade-left">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-secondary/10 text-secondary text-sm font-bold tracking-wide mb-4">SAMBUTAN PIMPINAN</span>
                    <h2 class="text-3xl sm:text-5xl font-extrabold text-gray-900 mb-8 leading-tight">Membangun Karakter Melalui Pendidikan Modern</h2>
                    
                    <div class="space-y-6 text-gray-600 text-lg leading-relaxed mb-10">
                        <p class="font-medium text-gray-900">Assalamu'alaikum Warahmatullahi Wabarakatuh,</p>
                        <p>Puji syukur kita panjatkan kehadirat Allah SWT atas segala rahmat dan karunia-Nya. Selamat datang di portal resmi SMA Negeri 1 Tapung Hulu, kawah candradimuka di tengah asrinya alam pedesaan Kabupaten Kampar.</p>
                        <p>Meski berada di kawasan yang lekat dengan nuansa agrikultur, semangat kami menghadirkan pendidikan modern tak pernah surut. Hadirnya Sistem Penjadwalan Digital cerdas ini adalah bukti nyata bahwa jarak bukanlah halangan untuk berinovasi dan maju sejajar dengan sekolah-sekolah di perkotaan.</p>
                        <p>Mari bersama-sama jadikan SMA Negeri 1 Tapung Hulu sebagai kebanggaan masyarakat desa—tempat lahirnya generasi emas yang tak hanya cakap teknologi, namun juga mencintai dan berbakti membangun tanah kelahirannya.</p>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?q=80&w=150&auto=format&fit=crop" alt="Signature" class="w-16 h-16 rounded-full object-cover shadow-md border-2 border-white lg:hidden">
                        <div>
                            <p class="font-extrabold text-gray-900">{{ explode(',', $kepsekNama)[0] }}</p>
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
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z"/>', 'title' => 'Ruang Kelas', 'desc' => 'Total 27 ruang kelas yang nyaman dan representatif untuk mendukung proses belajar mengajar secara optimal.'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>', 'title' => 'Laboratorium Terpadu', 'desc' => 'Memiliki 7 fasilitas laboratorium termasuk Lab IPA, Fisika, dan Kimia untuk kegiatan praktikum siswa.'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>', 'title' => 'Lab Komputer', 'desc' => 'Fasilitas Laboratorium Komputer untuk mendukung peningkatan literasi digital dan penguasaan teknologi informasi.'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>', 'title' => 'Perpustakaan', 'desc' => 'Perpustakaan sekolah yang menyediakan berbagai literatur dan referensi buku pelajaran untuk seluruh siswa.'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 8.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v8.25A2.25 2.25 0 006 16.5h2.25m8.25-8.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-7.5A2.25 2.25 0 018.25 18v-1.5m8.25-8.25h-6a2.25 2.25 0 00-2.25 2.25v6"/>', 'title' => 'Konektivitas Internet', 'desc' => 'Akses jaringan internet broadband yang mendukung kelancaran kegiatan belajar mengajar berbasis teknologi.'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>', 'title' => 'Listrik PLN', 'desc' => 'Dukungan daya listrik 6.600 VA yang memastikan kelancaran seluruh infrastruktur pembelajaran di sekolah.'],
                    ];
                @endphp

                @foreach($fasilitas as $item)
                    <div data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}" class="bg-white rounded-3xl p-8 border border-gray-100 shadow-lg shadow-gray-200/40 hover:shadow-2xl hover:shadow-primary/20 hover:-translate-y-2 hover:border-primary/20 transition-all duration-500 group relative overflow-hidden">
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

    {{-- Contact Section --}}
    <section id="kontak" class="py-24 bg-white border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="inline-block px-4 py-1.5 rounded-full bg-primary/10 text-primary text-sm font-bold tracking-wide mb-4">LOKASI & KONTAK</span>
                <h2 class="text-3xl sm:text-5xl font-extrabold text-gray-900 mb-6">Kunjungi Kami</h2>
            </div>
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="bg-gray-50 rounded-3xl p-10 border border-gray-100 shadow-lg shadow-gray-200/50" data-aos="fade-right">
                    <h3 class="text-2xl font-bold text-gray-900 mb-8">Informasi Kontak</h3>
                    <ul class="space-y-8">
                        <li class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 shadow-inner">
                                <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 text-lg">Alamat</h4>
                                <p class="text-gray-600 mt-1 leading-relaxed">Jl. Kampung Lama No. 10, Kec. Tapung Hulu, Kab. Kampar, Prov. Riau<br/>Lintang: 0.5787, Bujur: 100.9534</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-xl bg-secondary/10 flex items-center justify-center flex-shrink-0 shadow-inner">
                                <svg class="w-7 h-7 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 text-lg">Telepon</h4>
                                <p class="text-gray-600 mt-1 leading-relaxed">085271991329</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 shadow-inner">
                                <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 text-lg">Email</h4>
                                <p class="text-gray-600 mt-1 leading-relaxed">sma.negeri1.tapunghulu@gmail.com</p>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="rounded-3xl overflow-hidden shadow-2xl border border-gray-100 h-[500px]" data-aos="fade-left">
                    <iframe width="100%" height="100%" frameborder="0" src="https://maps.google.com/maps?q=0.5787,100.9534&output=embed" allowfullscreen loading="lazy" class="filter contrast-125 saturate-150"></iframe>
                </div>
            </div>
        </div>
    </section>

    {{-- Portal Login Section --}}
    <section id="portal-login" class="relative py-24 bg-gradient-to-b from-gray-50 to-primary-dark/5 border-t border-gray-100">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div data-aos="zoom-in" wire:ignore.self>
                <div class="bg-primary rounded-3xl p-8 sm:p-12 shadow-2xl shadow-primary/30 relative overflow-hidden">
                    <!-- Decorative background elements -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-secondary/20 rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-blue-500/20 rounded-full blur-2xl transform -translate-x-1/2 translate-y-1/2 pointer-events-none"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row gap-10 items-center justify-between">
                    <div class="md:w-1/2 text-center md:text-left">
                        <span class="inline-block px-4 py-1.5 rounded-full bg-secondary/20 text-secondary text-sm font-bold tracking-wide mb-4">AKSES INTERNAL</span>
                        <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4 leading-tight">Portal Akademik</h2>
                        <p class="text-gray-300 leading-relaxed text-lg mb-0">Silakan pilih peran dan masukkan kredensial Anda untuk mengakses sistem informasi akademik.</p>
                    </div>
                    
                    <div class="md:w-[45%] w-full bg-white/10 backdrop-blur-md p-6 sm:p-8 rounded-2xl border border-white/10">
                        <div class="flex p-1 bg-white/5 rounded-xl mb-6 border border-white/10">
                            <button wire:click="$set('loginTab', 'siswa_guru')" class="flex-1 py-2 text-sm font-bold rounded-lg transition-all {{ $loginTab === 'siswa_guru' ? 'bg-secondary text-primary shadow-md' : 'text-gray-400 hover:text-white' }}">Guru & Siswa</button>
                            <button wire:click="$set('loginTab', 'admin')" class="flex-1 py-2 text-sm font-bold rounded-lg transition-all {{ $loginTab === 'admin' ? 'bg-secondary text-primary shadow-md' : 'text-gray-400 hover:text-white' }}">Admin</button>
                        </div>
                        
                        @if($loginTab === 'siswa_guru')
                        <form wire:submit.prevent="loginSiswaGuru" class="space-y-5">
                            <div>
                                <label for="userId" class="block text-sm font-semibold text-gray-300 mb-2">NISN (Siswa) / NIP (Guru)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <input type="text" id="userId" wire:model="userId" class="block w-full pl-11 pr-4 py-3.5 bg-white/5 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-secondary focus:border-transparent transition-all sm:text-sm" placeholder="Ketik nomor induk Anda..." autocomplete="off">
                                </div>
                                @error('userId') <span class="text-red-400 text-xs mt-1.5 block font-medium">{{ $message }}</span> @enderror
                            </div>
                            
                            <button type="submit" class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-secondary hover:bg-yellow-400 text-primary font-bold rounded-xl transition-all duration-300 shadow-lg shadow-secondary/20 group">
                                Akses Portal
                                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </form>
                        @else
                        <form wire:submit.prevent="loginAdmin" class="space-y-5">
                            <div>
                                <label for="adminUsername" class="block text-sm font-semibold text-gray-300 mb-2">Username</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <input type="text" id="adminUsername" wire:model="adminUsername" class="block w-full pl-11 pr-4 py-3.5 bg-white/5 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-secondary focus:border-transparent transition-all sm:text-sm" placeholder="Ketik username admin..." autocomplete="off">
                                </div>
                                @error('adminUsername') <span class="text-red-400 text-xs mt-1.5 block font-medium">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="adminPassword" class="block text-sm font-semibold text-gray-300 mb-2">Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <input type="password" id="adminPassword" wire:model="adminPassword" class="block w-full pl-11 pr-4 py-3.5 bg-white/5 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-secondary focus:border-transparent transition-all sm:text-sm" placeholder="Ketik password admin...">
                                </div>
                                @error('adminPassword') <span class="text-red-400 text-xs mt-1.5 block font-medium">{{ $message }}</span> @enderror
                            </div>
                            
                            <button type="submit" class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-secondary hover:bg-yellow-400 text-primary font-bold rounded-xl transition-all duration-300 shadow-lg shadow-secondary/20 group">
                                Login Admin
                                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Minimalist Footer --}}
    <footer class="bg-primary-dark text-white border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-12 lg:gap-8">
                <div class="md:col-span-7">
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
                
                <div class="md:col-span-5">
                    <h4 class="text-lg font-bold text-white mb-6 uppercase tracking-wider">Navigasi</h4>
                    <ul class="space-y-3">
                        <li><a href="#tentang-kami" class="text-gray-400 hover:text-secondary hover:pl-2 transition-all duration-300 block">Profil Sekolah</a></li>
                        <li><a href="#visi-misi" class="text-gray-400 hover:text-secondary hover:pl-2 transition-all duration-300 block">Visi & Misi</a></li>
                        <li><a href="#fasilitas" class="text-gray-400 hover:text-secondary hover:pl-2 transition-all duration-300 block">Fasilitas</a></li>
                        <li><a href="#kontak" class="text-gray-400 hover:text-secondary hover:pl-2 transition-all duration-300 block">Lokasi & Kontak</a></li>
                        <li><a href="#portal-login" class="text-gray-400 hover:text-secondary hover:pl-2 transition-all duration-300 block">Portal Internal</a></li>
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
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('livewire:navigated', () => {
            AOS.init({
                once: true,
                offset: 50,
                duration: 800,
                easing: 'ease-out-cubic',
            });
        });
        document.addEventListener('DOMContentLoaded', () => {
            AOS.init({
                once: true,
                offset: 50,
                duration: 800,
                easing: 'ease-out-cubic',
            });
        });
    </script>
</div>
