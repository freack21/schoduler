<div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        {{-- Total Guru --}}
        <div class="stat-card">
            <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-7 h-7 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Total Guru</p>
                <p class="text-3xl font-bold text-gray-900">{{ $totalGuru }}</p>
            </div>
        </div>

        {{-- Total Siswa --}}
        <div class="stat-card">
            <div class="w-14 h-14 rounded-2xl bg-green-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Total Siswa</p>
                <p class="text-3xl font-bold text-gray-900">{{ $totalSiswa }}</p>
            </div>
        </div>

        {{-- Total Kelas --}}
        <div class="stat-card">
            <div class="w-14 h-14 rounded-2xl bg-purple-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-7 h-7 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Total Kelas</p>
                <p class="text-3xl font-bold text-gray-900">{{ $totalKelas }}</p>
            </div>
        </div>

        {{-- Total Mapel --}}
        <div class="stat-card">
            <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-7 h-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Total Mapel</p>
                <p class="text-3xl font-bold text-gray-900">{{ $totalMapel }}</p>
            </div>
        </div>
    </div>

    {{-- Quick Info Cards --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Selamat Datang!</h3>
            <p class="text-gray-600 text-sm leading-relaxed">
                Ini adalah panel admin Sistem Penjadwalan SMA Negeri 1 Tapung Hulu. Anda dapat mengelola data guru, siswa, kelas, mata pelajaran, serta men-generate jadwal otomatis menggunakan Algoritma Genetika.
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('admin.generate') }}" class="btn-primary text-sm">Generate Jadwal</a>
                <a href="{{ route('admin.guru') }}" class="btn-outline text-sm">Kelola Guru</a>
            </div>
        </div>

        <div class="card">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Panduan Cepat</h3>
            <div class="space-y-3">
                <div class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-primary text-white text-xs flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                    <p class="text-sm text-gray-600">Lengkapi data <strong>Guru</strong>, <strong>Siswa</strong>, <strong>Kelas</strong>, dan <strong>Mata Pelajaran</strong></p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-primary text-white text-xs flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                    <p class="text-sm text-gray-600">Atur <strong>Jam Pelajaran</strong> sesuai kebutuhan sekolah</p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-primary text-white text-xs flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                    <p class="text-sm text-gray-600"><strong>Assign mapel</strong> ke guru untuk setiap kelas</p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-secondary text-primary text-xs flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                    <p class="text-sm text-gray-600">Klik <strong>Generate Jadwal</strong> untuk membuat jadwal otomatis</p>
                </div>
            </div>
        </div>
    </div>
</div>
