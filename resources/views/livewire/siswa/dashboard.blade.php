<div>
    {{-- Profile Card --}}
    <div class="card mb-6">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                {{ strtoupper(substr(auth()->user()->nama_lengkap, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ auth()->user()->nama_lengkap }}</h2>
                <p class="text-sm text-gray-500 font-mono">NISN: {{ auth()->user()->id }}</p>
                @if($siswa)
                    <span class="badge bg-purple-50 text-purple-700 mt-1">Kelas {{ $siswa->kelas->nama }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Teman Sekelas --}}
    @if($teman->isNotEmpty())
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wider">Teman Sekelas</h3>
        <div class="flex gap-3 overflow-x-auto pb-2 -mx-1 px-1">
            @foreach($teman as $t)
                <div class="flex-shrink-0 w-24 text-center">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white font-bold mx-auto mb-2 shadow-sm text-sm">
                        {{ strtoupper(substr($t->user->nama_lengkap, 0, 2)) }}
                    </div>
                    <p class="text-xs text-gray-700 font-medium truncate">{{ $t->user->nama_lengkap }}</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Schedule --}}
    <div class="card !p-0 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Jadwal Pelajaran</h3>
            @if($siswa)
                <p class="text-sm text-gray-500">Kelas {{ $siswa->kelas->nama }}</p>
            @endif
        </div>

        @if($jadwal->isEmpty())
            <div class="text-center py-12 text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                <p>Jadwal belum tersedia. Hubungi admin.</p>
            </div>
        @else
            {{-- Mobile: Card Layout --}}
            <div class="block lg:hidden p-4 space-y-4">
                @foreach($allHari as $h)
                    @php $daySchedule = $jadwal->where('hari', $h)->sortBy(fn($j) => $j->jamPelajaran->jam_ke); @endphp
                    @if($daySchedule->isNotEmpty())
                        <div>
                            <h4 class="font-semibold text-primary text-sm mb-2">{{ $h }}</h4>
                            <div class="space-y-2">
                                @foreach($daySchedule as $j)
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                        <div class="text-center min-w-[50px]">
                                            <div class="text-sm font-bold text-primary">{{ substr($j->jamPelajaran->jam_mulai,0,5) }}</div>
                                            <div class="text-[10px] text-gray-400">{{ substr($j->jamPelajaran->jam_selesai,0,5) }}</div>
                                        </div>
                                        <div class="w-0.5 h-8 bg-secondary rounded-full"></div>
                                        <div>
                                            <p class="font-medium text-gray-900 text-sm">{{ $j->mapel->nama ?? '' }}</p>
                                            <p class="text-xs text-gray-500">{{ $j->guru->user->nama_lengkap ?? '' }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Desktop: Table Layout --}}
            <div class="hidden lg:block overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="table-header">
                        <tr>
                            <th class="px-3 py-2">Jam</th>
                            @foreach($allHari as $h)
                                <th class="px-3 py-2 text-center">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jamList as $jam)
                            <tr class="table-row">
                                <td class="px-3 py-2 font-medium bg-gray-50 text-center">
                                    <div>{{ $jam->jam_ke }}</div>
                                    <div class="text-[10px] text-gray-400">{{ substr($jam->jam_mulai,0,5) }}</div>
                                </td>
                                @foreach($allHari as $h)
                                    @php $entry = $jadwal->first(fn($j) => $j->hari === $h && $j->jam_pelajaran_id === $jam->id); @endphp
                                    <td class="px-2 py-1.5 text-center">
                                        @if($entry)
                                            <div class="bg-green-50 rounded px-2 py-1.5">
                                                <div class="font-bold text-green-700">{{ substr($entry->mapel->nama ?? '', 0, 15) }}...</div>
                                                <div class="text-[10px] text-gray-500 truncate">{{ $entry->guru->user->nama_lengkap ?? '' }}</div>
                                            </div>
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
