<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Reassemble Jadwal (Manual Editor)</h2>
            <p class="text-sm text-gray-500">Pindahkan atau tukar jadwal mapel dengan mudah. Klik blok untuk memilih, lalu klik slot tujuan.</p>
        </div>
        <div class="w-full sm:w-64">
            <select wire:model.live="kelas_id" class="input-field shadow-sm bg-white border-gray-200 text-gray-800">
                @foreach($kelasList as $k)
                    <option value="{{ $k->id }}">Kelas {{ $k->nama }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if($selectedJadwalId)
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-center justify-between text-blue-700 animate-pulse">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                <span class="text-sm font-semibold">1 Blok Terpilih. Klik slot kosong untuk memindah, atau blok lain untuk menukar (swap).</span>
            </div>
            <button wire:click="resetSelection" class="text-blue-500 hover:text-blue-700 font-bold p-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <div class="relative">
        {{-- Loading overlay --}}
        <div wire:loading wire:target="selectSlot, resetSelection" class="absolute inset-0 bg-white/70 backdrop-blur-[2px] z-20 flex items-center justify-center rounded-xl">
            <div class="flex items-center gap-3 bg-white px-5 py-3 rounded-xl shadow-lg border border-gray-100">
                <svg class="animate-spin h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-semibold text-gray-700">Menyimpan perubahan...</span>
            </div>
        </div>

        <div class="card !p-0 overflow-hidden shadow-sm border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-center">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-3 py-3 w-16 text-gray-500 uppercase text-xs font-bold tracking-wider">Jam Ke</th>
                            @foreach($hariAktif as $h)
                                <th class="px-4 py-3 text-gray-700 uppercase text-xs font-bold tracking-wider border-l border-gray-200">{{ ucfirst($h) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @for($i = 1; $i <= $maxJam; $i++)
                            <tr>
                                <td class="px-3 py-3 bg-gray-50 text-gray-600 font-bold border-r border-gray-200">{{ $i }}</td>
                                @foreach($hariAktif as $h)
                                    @php
                                        $jam = $rowMap[$i][$h] ?? null;
                                    @endphp
                                    
                                    @if(!$jam)
                                        <td class="px-4 py-3 bg-gray-50 border-r border-gray-100"></td>
                                    @elseif($jam->is_istirahat)
                                        <td class="px-4 py-3 bg-amber-50/50 border-r border-gray-100 relative group">
                                            <div class="flex flex-col items-center justify-center py-2 text-amber-600/70">
                                                <svg class="w-4 h-4 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span class="text-[10px] font-bold uppercase tracking-wider">Istirahat</span>
                                            </div>
                                        </td>
                                    @else
                                        @php
                                            $jadwal = $matrix[$jam->id][$h] ?? null;
                                            $isSelected = $selectedJadwalId && $jadwal && $jadwal->id === $selectedJadwalId;
                                        @endphp
                                        
                                        <td class="px-2 py-2 border-r border-gray-100 relative {{ $isSelected ? 'bg-blue-50/50' : 'bg-white hover:bg-gray-50/50' }} transition-colors cursor-pointer" 
                                            wire:click="selectSlot('{{ $h }}', {{ $jam->id }}, {{ $jadwal ? $jadwal->id : 'null' }})">
                                            
                                            @if($jadwal)
                                                <div class="h-full min-h-[4rem] rounded-lg p-2 flex flex-col items-center justify-center border-2 {{ $isSelected ? 'border-blue-500 shadow-md bg-white' : 'border-emerald-100/50 bg-emerald-50/50 hover:border-emerald-300' }} transition-all">
                                                    <span class="font-bold text-gray-900 text-xs mb-1">{{ $jadwal->mapel->kode }}</span>
                                                    @php
                                                        $namaSingkat = explode(',', $jadwal->guru->user->nama_lengkap)[0];
                                                    @endphp
                                                    <span class="text-[10px] bg-white px-2 py-0.5 rounded-full text-emerald-700 border border-emerald-200/50 font-medium whitespace-nowrap">{{ $namaSingkat }}</span>
                                                </div>
                                            @else
                                                <div class="h-full min-h-[4rem] rounded-lg border-2 border-dashed border-gray-200 flex items-center justify-center text-gray-400 hover:border-blue-300 hover:bg-blue-50/30 transition-colors">
                                                    @if($selectedJadwalId)
                                                        <span class="text-[10px] font-medium">Pindah ke sini</span>
                                                    @else
                                                        <span class="text-[10px]">-</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
