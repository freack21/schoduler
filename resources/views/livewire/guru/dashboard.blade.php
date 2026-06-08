<div>
    {{-- Profile Card --}}
    <div class="card mb-6">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary to-primary-light flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                {{ strtoupper(substr(auth()->user()->nama_lengkap, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ auth()->user()->nama_lengkap }}</h2>
                <p class="text-sm text-gray-500 font-mono">NIP: {{ auth()->user()->id }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Pengajar</p>
            </div>
        </div>
    </div>

    {{-- Mapel yang Diampu --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wider">Mata Pelajaran yang Diampu</h3>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @forelse($guruMapels->groupBy('mapel.nama') as $mapelNama => $items)
                <div class="card !p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                        </div>
                        <h4 class="font-semibold text-gray-900">{{ $mapelNama }}</h4>
                    </div>
                    <div class="flex flex-wrap gap-1.5 ml-12">
                        @foreach($items as $gm)
                            <span class="badge bg-primary/10 text-primary">{{ $gm->kelas->nama }}</span>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-gray-400 py-6 text-sm">Belum ada mapel yang diampu.</div>
            @endforelse
        </div>
    </div>

    {{-- Schedule Tabs --}}
    <div class="card !p-0 overflow-hidden">
        <div class="flex border-b border-gray-200">
            <button wire:click="$set('activeTab', 'hari-ini')" class="px-6 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer {{ $activeTab === 'hari-ini' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Hari Ini ({{ $hariIni }})
            </button>
            <button wire:click="$set('activeTab', 'mingguan')" class="px-6 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer {{ $activeTab === 'mingguan' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Mingguan
            </button>
        </div>

        <div class="p-6">
            @if($activeTab === 'hari-ini')
                {{-- Today's Schedule --}}
                @if($jadwalHariIni->isEmpty())
                    <div class="text-center py-8 text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                        <p>Tidak ada jadwal mengajar hari ini.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($jadwalHariIni as $j)
                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="text-center min-w-[70px]">
                                    <div class="text-xs text-gray-400">Jam ke-{{ $j->jamPelajaran->jam_ke }}</div>
                                    <div class="text-sm font-semibold text-primary">{{ substr($j->jamPelajaran->jam_mulai,0,5) }}</div>
                                    <div class="text-xs text-gray-400">{{ substr($j->jamPelajaran->jam_selesai,0,5) }}</div>
                                </div>
                                <div class="w-1 h-12 rounded-full bg-secondary"></div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $j->mapel->nama ?? '' }}</p>
                                    <p class="text-sm text-gray-500">Kelas {{ $j->kelas->nama ?? '' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                {{-- Weekly Schedule --}}
                <div class="overflow-x-auto -mx-6">
                    <table class="w-full text-xs min-w-[600px]">
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
                                        @php
                                            $entry = $jadwalMingguan->get($h, collect())->first(fn($j) => $j->jamPelajaran->jam_ke === $jam->jam_ke);
                                        @endphp
                                        <td class="px-2 py-1.5 text-center">
                                            @if($entry)
                                                <div class="bg-blue-50 rounded px-2 py-1">
                                                    <div class="font-bold text-blue-700">{{ substr($entry->mapel->nama ?? '', 0, 15) }}...</div>
                                                    <div class="text-[10px] text-gray-500">{{ $entry->kelas->nama ?? '' }}</div>
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
</div>
