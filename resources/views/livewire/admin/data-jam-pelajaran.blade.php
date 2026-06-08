<div>
    {{-- Header --}}
    <div class="card bg-gradient-to-br from-white to-gray-50/50 shadow-sm border border-gray-100/50 mb-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
        <div class="relative z-10">
            <h2 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Jam Pelajaran & Waktu
            </h2>
            <p class="text-sm font-medium text-gray-500 mt-1">Atur urutan jam pelajaran dan istirahat per hari secara fleksibel.</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach($hariList as $h)
            <button wire:click="$set('hariFilter', '{{ $h }}')" 
                class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all duration-300 {{ $hariFilter === $h ? 'bg-primary text-white shadow-md shadow-primary/20 scale-105' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200' }}">
                {{ $h }}
            </button>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- Main Kanban Area --}}
        <div class="lg:col-span-2 space-y-4">
            
            @if($jamList->isEmpty())
                <div class="card bg-white/50 border-dashed border-2 flex flex-col items-center justify-center py-12 text-center">
                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="text-gray-500 font-medium">Belum ada jam di hari {{ $hariFilter }}.</p>
                    <p class="text-sm text-gray-400 mt-1">Mulai tambahkan jam pelajaran atau salin dari hari lain.</p>
                </div>
            @endif

            <div class="space-y-3">
                @foreach($jamList as $index => $jam)
                    <div class="card p-0 flex items-stretch overflow-hidden group transition-all duration-300 hover:shadow-md border {{ $jam->is_istirahat ? 'border-amber-200 bg-amber-50/30' : 'border-blue-100 bg-white' }}" wire:key="jam-{{ $jam->id }}">
                        
                        {{-- Controls --}}
                        <div class="flex flex-col border-r {{ $jam->is_istirahat ? 'border-amber-200 bg-amber-100/50' : 'border-blue-100 bg-blue-50/50' }} w-12 items-center justify-center py-2 gap-1">
                            @if(!$loop->first)
                                <button wire:click="moveBlock({{ $jam->id }}, 'up')" class="p-1 rounded text-gray-400 hover:text-primary hover:bg-white transition-colors" title="Geser ke atas">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" /></svg>
                                </button>
                            @else
                                <div class="w-6 h-6"></div>
                            @endif
                            
                            <span class="text-xs font-black text-gray-500">{{ $jam->jam_ke }}</span>
                            
                            @if(!$loop->last)
                                <button wire:click="moveBlock({{ $jam->id }}, 'down')" class="p-1 rounded text-gray-400 hover:text-primary hover:bg-white transition-colors" title="Geser ke bawah">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                            @else
                                <div class="w-6 h-6"></div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="p-4 flex-1 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                @if($jam->is_istirahat)
                                    <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <div class="flex-1">
                                        <input type="text" 
                                               wire:change="updateNamaKegiatan({{ $jam->id }}, $event.target.value)" 
                                               value="{{ $jam->nama_kegiatan }}" 
                                               placeholder="Kegiatan Khusus / Istirahat" 
                                               class="w-full bg-transparent border-none text-base font-bold text-amber-800 p-0 focus:ring-0 placeholder-amber-400/70"
                                               title="Ketik nama kegiatan (misal: Upacara Bendera, Kultum, dll)">
                                        <p class="text-xs font-semibold text-amber-600 font-mono mt-0.5">{{ substr($jam->jam_mulai, 0, 5) }} – {{ substr($jam->jam_selesai, 0, 5) }}</p>
                                    </div>
                                @else
                                    <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                        <span class="font-black text-sm">J{{ $jam->jam_ke }}</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-800 text-base">Jam Pelajaran</h3>
                                        <p class="text-xs font-semibold text-primary font-mono mt-0.5">{{ substr($jam->jam_mulai, 0, 5) }} – {{ substr($jam->jam_selesai, 0, 5) }}</p>
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2 bg-gray-50/80 px-3 py-1.5 rounded-lg border border-gray-200/50">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <input type="number" 
                                           wire:change="updateDuration({{ $jam->id }}, $event.target.value)" 
                                           value="{{ $jam->durasi_menit }}" 
                                           class="w-14 bg-transparent border-none text-sm font-bold text-gray-700 p-0 focus:ring-0 text-center">
                                    <span class="text-xs font-bold text-gray-500">Mnt</span>
                                </div>
                                <button wire:click="removeBlock({{ $jam->id }})" class="p-2 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Hapus">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 mt-4">
                <button wire:click="addBlock(false)" class="btn-primary py-2.5 flex-1 flex items-center justify-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Tambah Jam
                </button>
                <button wire:click="addBlock(true)" class="btn-outline py-2.5 flex-1 flex items-center justify-center gap-2 text-sm border-amber-200 text-amber-700 hover:bg-amber-50">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Tambah Jam Khusus
                </button>
            </div>
        </div>

        {{-- Sidebar Settings --}}
        <div class="space-y-6">
            <div class="card bg-white border border-gray-100 shadow-sm">
                <h3 class="text-sm font-black text-gray-800 mb-4 flex items-center gap-2 uppercase tracking-wide">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    Pengaturan Harian
                </h3>
                
                <div class="mb-4">
                    <label class="label-field text-xs">Waktu Mulai Sekolah ({{ $hariFilter }})</label>
                    <div class="flex gap-2">
                        <input type="time" wire:model="jamMulaiHari" class="input-field py-2" required>
                        <button wire:click="updateWaktu" class="btn-primary py-2 px-4 whitespace-nowrap">Terapkan</button>
                    </div>
                    @error('jamMulaiHari') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="card bg-gray-50/50 border border-gray-100 shadow-sm">
                <h3 class="text-sm font-black text-gray-800 mb-4 flex items-center gap-2 uppercase tracking-wide">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    Salin Jadwal
                </h3>
                <p class="text-xs text-gray-500 mb-3 leading-relaxed">Salin semua susunan jam pelajaran dari <strong>{{ $hariFilter }}</strong> ke hari lain secara instan.</p>
                
                <div class="space-y-2">
                    @foreach($hariList as $h)
                        @if($h !== $hariFilter)
                            <button wire:click="copyTo('{{ $h }}')" 
                                onclick="confirm('Yakin ingin menyalin ke hari {{ $h }}? Jadwal {{ $h }} yang lama akan tertimpa.') || event.stopImmediatePropagation()"
                                class="w-full py-2 px-3 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:border-primary hover:text-primary transition-colors text-left flex justify-between items-center group">
                                Ke {{ $h }}
                                <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
