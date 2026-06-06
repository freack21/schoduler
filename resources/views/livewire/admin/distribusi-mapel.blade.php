<div>
    {{-- Header --}}
    <div class="card bg-gradient-to-br from-white to-gray-50/50 shadow-sm border border-gray-100/50 mb-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                    <svg class="w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Pemetaan Tugas & Distribusi Mapel
                </h2>
                <p class="text-sm font-medium text-gray-500 mt-1">Daftar hubungan Mata Pelajaran, Kelas, dan Guru Pengampu.</p>
            </div>
            <div>
                <a href="{{ route('admin.guru') }}" class="btn-primary text-sm whitespace-nowrap">
                    Kelola Data Guru
                </a>
            </div>
        </div>
    </div>

    {{-- Mapel List Grid --}}
    <div class="space-y-6">
        @foreach($mapels as $mapel)
            @php
                $mapelAssignments = $groupedAssignments[$mapel->id] ?? [];
                // Check if any class is missing a teacher
                $missingClassesCount = 0;
                foreach($kelasList as $kelas) {
                    if(empty($mapelAssignments[$kelas->id])) {
                        $missingClassesCount++;
                    }
                }
            @endphp
            
            <div class="card p-0 overflow-hidden bg-white/70 backdrop-blur-md border {{ $missingClassesCount > 0 ? 'border-amber-200 shadow-amber-500/10' : 'border-gray-200/50' }} transition-all duration-300 hover:shadow-lg">
                {{-- Card Header --}}
                <div class="px-6 py-4 {{ $missingClassesCount > 0 ? 'bg-amber-50/50' : 'bg-gray-50/50' }} border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <span class="w-2 h-6 {{ $missingClassesCount > 0 ? 'bg-amber-400' : 'bg-primary' }} rounded-full"></span>
                            {{ $mapel->nama }}
                        </h3>
                        <div class="text-xs font-semibold text-gray-500 mt-1 flex items-center gap-3">
                            <span class="badge bg-white shadow-sm">{{ $mapel->kode }}</span>
                            <span>{{ $mapel->jam_per_minggu }} Jam/Minggu</span>
                        </div>
                    </div>
                    
                    @if($missingClassesCount > 0)
                        <div class="flex items-center gap-2 text-amber-600 bg-amber-100/50 px-3 py-1.5 rounded-lg text-sm font-semibold">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            {{ $missingClassesCount }} Kelas Belum Terisi
                        </div>
                    @else
                        <div class="flex items-center gap-2 text-green-600 bg-green-50 px-3 py-1.5 rounded-lg text-sm font-semibold">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Semua Kelas Terisi
                        </div>
                    @endif
                </div>

                {{-- Kelas Grid --}}
                <div class="p-6">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-7 gap-3">
                        @foreach($kelasList as $kelas)
                            @php
                                $gurus = $mapelAssignments[$kelas->id] ?? [];
                                $hasGuru = count($gurus) > 0;
                            @endphp
                            
                            <div class="rounded-xl border {{ $hasGuru ? 'border-gray-200 bg-white' : 'border-amber-200 bg-amber-50/30' }} p-3 relative overflow-hidden group">
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">{{ $kelas->nama }}</div>
                                
                                @if($hasGuru)
                                    @foreach($gurus as $guruName)
                                        <div class="text-sm font-semibold text-gray-800 leading-tight mb-1 truncate" title="{{ $guruName }}">
                                            {{ $guruName }}
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-sm font-bold text-amber-600 italic">KOSONG</div>
                                @endif
                                
                                @if(!$hasGuru)
                                    <div class="absolute inset-x-0 bottom-0 h-1 bg-amber-400"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
