<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Reassemble Jadwal (Manual Editor)</h2>
            <p class="text-sm text-gray-500">Pindahkan, tukar, hapus jadwal mapel, atau sisipkan mapel baru pada slot kosong.</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="confirmClearClass" class="btn-danger flex items-center gap-2 text-sm shadow-sm whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Kosongkan
            </button>
            <div class="w-full sm:w-48">
                <select wire:model.live="kelas_id" class="input-field shadow-sm bg-white border-gray-200 text-gray-800 font-bold">
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id }}">Kelas {{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Curriculum Stats Section --}}
    @if(isset($curriculumStats) && count($curriculumStats) > 0)
        <div class="card !p-4 mb-6 bg-white border border-gray-200 shadow-sm">
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Status Kurikulum Kelas</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($curriculumStats as $stat)
                    <div class="px-3 py-1.5 rounded-lg border text-xs font-medium flex items-center gap-2 {{ $stat['is_complete'] ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-amber-50 border-amber-200 text-amber-700' }}">
                        <span>{{ $stat['kode'] }}</span>
                        <div class="w-px h-3 bg-current opacity-30"></div>
                        <span>{{ $stat['filled'] }}/{{ $stat['required'] }}</span>
                        @if(!$stat['is_complete'])
                            <span class="bg-amber-200 text-amber-800 px-1.5 py-0.5 rounded-md text-[10px] font-bold">-{{ $stat['missing'] }}</span>
                        @else
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

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
        <div wire:loading.delay wire:target="selectSlot, resetSelection" class="absolute inset-0 bg-white/70 backdrop-blur-[2px] z-20 flex items-center justify-center rounded-xl">
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
                                        
                                        <td class="px-2 py-2 border-r border-gray-100 relative {{ $isSelected ? 'bg-blue-50/50' : 'bg-white hover:bg-gray-50/50' }} transition-colors cursor-pointer group" 
                                            wire:click="selectSlot('{{ $h }}', {{ $jam->id }}, {{ $jadwal ? $jadwal->id : 'null' }})">
                                            
                                            @if($jadwal)
                                                <div class="h-full min-h-[4rem] rounded-lg p-2 flex flex-col items-center justify-center border-2 {{ $isSelected ? 'border-blue-500 shadow-md bg-white' : 'border-emerald-100/50 bg-emerald-50/50 hover:border-emerald-300' }} transition-all relative">
                                                    <span class="font-bold text-gray-900 text-xs mb-1">{{ $jadwal->mapel->kode }}</span>
                                                    @php
                                                        $namaSingkat = explode(',', $jadwal->guru->user->nama_lengkap)[0];
                                                    @endphp
                                                    <span class="text-[10px] bg-white px-2 py-0.5 rounded-full text-emerald-700 border border-emerald-200/50 font-medium whitespace-nowrap">{{ $namaSingkat }}</span>
                                                    
                                                    {{-- Delete Button (shows on hover) --}}
                                                    <button wire:click.stop="confirmDeleteBlock({{ $jadwal->id }})" class="absolute -top-2 -right-2 bg-red-500 hover:bg-red-600 text-white p-1 rounded-full shadow-sm opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
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

    {{-- Insert Manual Modal --}}
    @if($showInsertModal)
        <div class="modal-overlay">
            <div class="modal-content !max-w-md">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-800">Sisipkan Mapel Manual</h3>
                    <button wire:click="$set('showInsertModal', false)" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form wire:submit.prevent="insertManual" class="p-6 space-y-5">
                    <div class="bg-blue-50 text-blue-700 text-sm p-3 rounded-lg flex items-start gap-2 mb-4 border border-blue-100">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p>Menambahkan mapel ke slot <b>{{ ucfirst($insertHari) }}</b>, Jam ID: <b>{{ $insertJamId }}</b>.</p>
                    </div>

                    <div>
                        <label class="label-field">Mata Pelajaran (Belum Terpenuhi)</label>
                        <select wire:model.live="insertMapelId" class="input-field" required>
                            <option value="">-- Pilih Mapel --</option>
                            @foreach($availableMapel as $mapel)
                                <option value="{{ $mapel['id'] }}">{{ $mapel['nama'] }}</option>
                            @endforeach
                        </select>
                        @error('insertMapelId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    @if($insertMapelId)
                        <div>
                            <label class="label-field">Guru Pengampu</label>
                            <select wire:model="insertGuruId" class="input-field" required>
                                <option value="">-- Pilih Guru --</option>
                                @foreach($availableGurus as $guru)
                                    <option value="{{ $guru['id'] }}">{{ $guru['nama'] }}</option>
                                @endforeach
                            </select>
                            @if(empty($availableGurus))
                                <p class="text-xs text-amber-600 mt-1">Tidak ada guru yang ditugaskan untuk mapel ini di kelas terkait. Silakan cek menu Assign Guru.</p>
                            @endif
                            @error('insertGuruId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                        <button type="button" wire:click="$set('showInsertModal', false)" class="btn-outline">Batal</button>
                        <button type="submit" class="btn-primary flex items-center gap-2" {{ empty($availableGurus) ? 'disabled' : '' }}>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Simpan ke Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
