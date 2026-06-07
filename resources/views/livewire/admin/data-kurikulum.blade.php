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

    {{-- Table --}}
    <div class="card !p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="table-header">
                    <tr>
                        <th class="px-6 py-3 w-16">No</th>
                        <th class="px-6 py-3">Tingkat</th>
                        <th class="px-6 py-3">Jurusan</th>
                        <th class="px-6 py-3">Mata Pelajaran</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kurikulumList as $i => $item)
                        <tr class="table-row">
                            <td class="px-6 py-4 text-gray-500">{{ $i + 1 }}</td>
                            <td class="px-6 py-4 font-bold text-primary">{{ $item->tingkat->nama }}</td>
                            <td class="px-6 py-4">
                                @if($item->jurusan)
                                    <span class="badge bg-purple-50 text-purple-700">{{ $item->jurusan->kode }}</span>
                                @else
                                    <span class="text-gray-400 italic text-xs">Semua (Umum)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $item->mapel->nama }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="confirmDelete({{ $item->id }})" class="p-1.5 rounded-lg hover:bg-red-50 text-red-500 transition-colors" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">Belum ada data kurikulum.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
