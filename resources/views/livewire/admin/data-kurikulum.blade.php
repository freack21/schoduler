<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Master Data Kurikulum</h2>
            <p class="text-sm text-gray-500">Atur mapel apa saja yang diajarkan pada tingkat dan jurusan tertentu.</p>
        </div>
        <button wire:click="openCreateModal" class="btn-primary flex items-center gap-2 text-sm whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah ke Kurikulum
        </button>
    </div>

    {{-- Grouped Data --}}
    <div class="space-y-6">
        @forelse($groupedKurikulum as $key => $group)
            <div class="card !p-0 overflow-hidden border border-gray-100 shadow-sm">
                <div class="bg-gray-50/80 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-bold">
                            {{ explode(' ', $group['tingkat_nama'])[1] ?? '?' }}
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-lg">{{ $group['tingkat_nama'] }}</h3>
                            <p class="text-sm text-gray-500 font-medium">{{ $group['jurusan_nama'] }}</p>
                        </div>
                    </div>
                    @if($group['jurusan_kode'] !== 'UMUM')
                        <span class="badge bg-purple-50 text-purple-700 px-3 py-1 text-sm border border-purple-100/50 shadow-sm">{{ $group['jurusan_kode'] }}</span>
                    @else
                        <span class="badge bg-gray-100 text-gray-600 px-3 py-1 text-sm border border-gray-200/50 shadow-sm">UMUM</span>
                    @endif
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-white text-gray-500 text-xs uppercase font-medium border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-3 w-16 text-center">No</th>
                                <th class="px-6 py-3">Mata Pelajaran</th>
                                <th class="px-6 py-3 w-32 text-center">Jam/Minggu</th>
                                <th class="px-6 py-3">Guru Pengampu</th>
                                <th class="px-6 py-3 text-right w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($group['items'] as $i => $item)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 text-center text-gray-400 font-medium">{{ $i + 1 }}</td>
                                    <td class="px-6 py-4 font-bold text-gray-900">{{ $item['mapel']->nama }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 font-bold text-xs border border-blue-100/50">
                                            {{ $item['mapel']->jam_per_minggu }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if(count($item['gurus']) > 0)
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($item['gurus'] as $gm)
                                                    @php
                                                        $namaSingkat = explode(',', $gm->guru->user->nama_lengkap)[0];
                                                    @endphp
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 text-xs font-medium border border-emerald-100/50" title="{{ $gm->guru->user->nama_lengkap }}">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>
                                                        {{ $namaSingkat }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-red-500 italic flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                Belum ada guru
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end">
                                            <button wire:click="confirmDelete({{ $item['id'] }})" class="p-1.5 rounded-lg hover:bg-red-50 text-red-500 transition-colors" title="Hapus dari kurikulum ini">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="card text-center py-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Kurikulum Kosong</h3>
                <p class="text-sm text-gray-500">Belum ada mata pelajaran yang ditambahkan ke kurikulum tingkat manapun.</p>
            </div>
        @endforelse
    </div>

    {{-- Create Modal --}}
    @if($showModal)
    <div class="modal-overlay" wire:click.self="$set('showModal', false)">
        <div class="modal-content" @click.stop>
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Tambah ke Kurikulum</h3>
            </div>
            <form wire:submit="save" class="p-6 space-y-4">
                <div>
                    <label class="label-field">Tingkat</label>
                    <select wire:model="tingkat_id" class="input-field">
                        <option value="0">Pilih Tingkat</option>
                        @foreach($tingkatList as $t)
                            <option value="{{ $t->id }}">{{ $t->nama }} (Kelas {{ $t->kode }})</option>
                        @endforeach
                    </select>
                    @error('tingkat_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Jurusan (Opsional - Jika spesifik untuk peminatan)</label>
                    <select wire:model="jurusan_id" class="input-field">
                        <option value="">Semua Jurusan (Umum)</option>
                        @foreach($jurusanList as $jurusan)
                            <option value="{{ $jurusan->id }}">{{ $jurusan->kode }} - {{ $jurusan->nama }}</option>
                        @endforeach
                    </select>
                    @error('jurusan_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Mata Pelajaran</label>
                    <select wire:model="mapel_id" class="input-field">
                        <option value="0">Pilih Mapel</option>
                        @foreach($mapelList as $mapel)
                            <option value="{{ $mapel->id }}">{{ $mapel->nama }}</option>
                        @endforeach
                    </select>
                    @error('mapel_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-outline text-sm">Batal</button>
                    <button type="submit" class="btn-primary text-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
