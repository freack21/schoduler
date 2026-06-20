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
                            <span class="badge bg-primary/10 text-primary">{{ $gm->tingkat->nama ?? '' }} {{ $gm->jurusan->nama ?? '' }}</span>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-gray-400 py-6 text-sm">Belum ada mapel yang diampu.</div>
            @endforelse
        </div>
    </div>

    {{-- Schedule Tabs --}}
    <div class="card !p-0 overflow-hidden mb-6">
        <div class="flex border-b border-gray-200 justify-between items-center pr-6 flex-wrap gap-4">
            <div class="flex">
                <button wire:click="$set('activeTab', 'hari-ini')" class="px-6 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer {{ $activeTab === 'hari-ini' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Hari Ini ({{ $hariIni }})
                </button>
                <button wire:click="$set('activeTab', 'mingguan')" class="px-6 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer {{ $activeTab === 'mingguan' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Mingguan
                </button>
                <button wire:click="$set('activeTab', 'modul-jar')" class="px-6 py-3 text-sm font-medium border-b-2 transition-colors cursor-pointer {{ $activeTab === 'modul-jar' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Modul Ajar / Silabus
                </button>
            </div>
            
            <div class="flex items-center gap-3 py-2 pr-2">
                <select wire:model.live="selectedTahunAjaran" class="input-field py-1 px-3 text-xs w-48">
                    @foreach($tahunAjaranList as $ta)
                        <option value="{{ $ta }}">{{ $ta }}</option>
                    @endforeach
                </select>

                @if($guru)
                <a href="/export/jadwal/guru?ids[]={{ $guru->id }}&tahun_ajaran={{ urlencode($selectedTahunAjaran) }}" target="_blank" class="px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Ekspor PDF
                </a>
                @endif
            </div>
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
            @elseif($activeTab === 'mingguan')
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
            @else
                {{-- Modul Ajar / Silabus --}}
                <div class="space-y-4">
                    <div class="flex justify-between items-center flex-wrap gap-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 text-sm">Daftar Modul Ajar / Silabus Anda</h4>
                            <p class="text-xs text-gray-500 mt-0.5">Upload modul ajar untuk mata pelajaran yang Anda ampu.</p>
                        </div>
                        
                        @if(empty(auth()->user()->password))
                            <div class="text-xs text-red-600 bg-red-50 px-3 py-2 border border-red-100 rounded-lg max-w-sm">
                                <strong>Pemberitahuan:</strong> Password Anda belum diatur. Silakan hubungi admin untuk mengisi password akun Anda agar dapat mengelola modul ajar.
                            </div>
                        @else
                            <button wire:click="openUploadModal" class="px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2 shadow-sm cursor-pointer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Upload Modul Ajar
                            </button>
                        @endif
                    </div>

                    @if($modulAjars->isEmpty())
                        <div class="text-center py-8 text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                            <p class="text-sm">Anda belum mengupload modul ajar apa pun.</p>
                        </div>
                    @else
                        <div class="grid sm:grid-cols-2 gap-4">
                            @foreach($modulAjars as $ma)
                                <div class="p-4 border border-gray-200 rounded-xl bg-white flex justify-between items-start gap-4">
                                    <div class="min-w-0">
                                        <h5 class="font-semibold text-gray-900 text-sm truncate" title="{{ $ma->nama_file }}">{{ $ma->nama_file }}</h5>
                                        <p class="text-xs text-gray-500 mt-0.5">Mapel: {{ $ma->mapel->nama }}</p>
                                        <p class="text-[10px] text-gray-400 mt-1">Diupload pada: {{ $ma->created_at->format('d M Y H:i') }}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="{{ asset('storage/' . $ma->file_path) }}" target="_blank" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Download">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        </a>
                                        <button wire:click="confirmDeleteModulAjar({{ $ma->id }})" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL UPLOAD MODUL AJAR --}}
    @if($showUploadModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 animate-fadeIn">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
                <div class="flex justify-between items-center border-b pb-3">
                    <h3 class="font-bold text-gray-900 text-lg">Upload Modul Ajar / Silabus</h3>
                    <button wire:click="$set('showUploadModal', false)" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="uploadModulAjar" class="space-y-4">
                    <div>
                        <label class="label-field block text-xs font-semibold uppercase tracking-wider mb-1.5">Pilih Mata Pelajaran</label>
                        <select wire:model="selectedMapelId" class="input-field w-full">
                            <option value="">-- Pilih Mapel --</option>
                            @foreach($uniqueMapels as $m)
                                <option value="{{ $m->id }}">{{ $m->nama }}</option>
                            @endforeach
                        </select>
                        @error('selectedMapelId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="label-field block text-xs font-semibold uppercase tracking-wider mb-1.5">File Modul Ajar (PDF/Docx/etc max 10MB)</label>
                        <input type="file" wire:model="modulAjarFile" class="input-field w-full py-1">
                        @error('modulAjarFile') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="label-field block text-xs font-semibold uppercase tracking-wider mb-1.5">Konfirmasi Password Akun Anda</label>
                        <input type="password" wire:model="passwordConfirm" class="input-field w-full" placeholder="Ketik password untuk verifikasi...">
                        @error('passwordConfirm') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="$set('showUploadModal', false)" class="btn-outline text-xs cursor-pointer">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" class="btn-primary text-xs flex items-center gap-1.5 cursor-pointer">
                            <span wire:loading wire:target="modulAjarFile" class="animate-spin rounded-full h-3.5 w-3.5 border-2 border-white border-t-transparent"></span>
                            Simpan Modul
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL DELETE VERIFICATION --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 animate-fadeIn">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
                <div class="flex justify-between items-center border-b pb-3">
                    <h3 class="font-bold text-gray-900 text-lg">Konfirmasi Hapus Modul Ajar</h3>
                    <button wire:click="$set('showDeleteModal', false)" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="text-sm text-gray-600">
                    Untuk menghapus berkas modul ajar ini, silakan ketik password akun Anda untuk melakukan verifikasi keamanan.
                </div>

                <form wire:submit.prevent="deleteModulAjar" class="space-y-4">
                    <div>
                        <label class="label-field block text-xs font-semibold uppercase tracking-wider mb-1.5">Password Anda</label>
                        <input type="password" wire:model="deletePasswordConfirm" class="input-field w-full" placeholder="Ketik password Anda...">
                        @error('deletePasswordConfirm') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="$set('showDeleteModal', false)" class="btn-outline text-xs cursor-pointer">Batal</button>
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-xl text-xs font-semibold hover:bg-red-700 transition-colors shadow-sm cursor-pointer">Verifikasi & Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
