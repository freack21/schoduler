<div>
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Kelola mata pelajaran dan alokasi jam per minggu</p>
        <button wire:click="openCreateModal" class="btn-primary flex items-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah Mapel
        </button>
    </div>

    <div class="card !p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="table-header">
                    <tr>
                        <th class="px-6 py-3">No</th>
                        <th class="px-6 py-3">Kode</th>
                        <th class="px-6 py-3">Nama Mata Pelajaran</th>
                        <th class="px-6 py-3">Jam/Minggu</th>
                        <th class="px-6 py-3">Jam/Hari</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mapelList as $i => $mapel)
                        <tr class="table-row" wire:key="mapel-{{ $mapel->id }}">
                            <td class="px-6 py-4 text-gray-500">{{ $i + 1 }}</td>
                            <td class="px-6 py-4"><span class="badge bg-amber-50 text-amber-700 font-mono">{{ $mapel->kode }}</span></td>
                            <td class="px-6 py-4 font-medium text-gray-900">
                                {{ $mapel->nama }}
                                @if($mapel->is_parallel)
                                    <span class="ml-2 badge bg-indigo-50 text-indigo-700 text-xs">Paralel / Multi-Guru</span>
                                    @if($mapel->kelompok_paralel)
                                        <span class="ml-1 badge bg-indigo-100 text-indigo-800 text-xs border border-indigo-200">{{ $mapel->kelompok_paralel }}</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1 text-gray-600">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ $mapel->jam_per_minggu }} jam
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="badge bg-purple-50 text-purple-700">{{ $mapel->jam_per_hari }} jam/hari</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="openEditModal({{ $mapel->id }})" class="p-1.5 rounded-lg hover:bg-blue-50 text-blue-600 transition-colors cursor-pointer"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg></button>
                                    <button wire:click="confirmDelete({{ $mapel->id }})" class="p-1.5 rounded-lg hover:bg-red-50 text-red-500 transition-colors cursor-pointer"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400">Belum ada data mapel.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($showModal)
    <div class="modal-overlay" wire:click.self="$set('showModal', false)">
        <div class="modal-content" @click.stop>
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit Mapel' : 'Tambah Mapel Baru' }}</h3>
            </div>
            <form wire:submit="save" class="p-6 space-y-4">
                <div>
                    <label class="label-field">Kode</label>
                    <input wire:model="kode" type="text" class="input-field" placeholder="Contoh: MTK, FIS, BIO">
                    @error('kode') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Nama Mata Pelajaran</label>
                    <input wire:model="nama" type="text" class="input-field" placeholder="Contoh: Matematika">
                    @error('nama') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label-field">Jam per Minggu</label>
                        <input wire:model="jam_per_minggu" type="number" min="1" max="10" class="input-field">
                        @error('jam_per_minggu') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label-field">Jam/Hari</label>
                        <input wire:model="jam_per_hari" type="number" min="1" max="10" class="input-field">
                        <p class="text-xs text-gray-400 mt-1">Ukuran blok jam per hari</p>
                        @error('jam_per_hari') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="is_parallel" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                        <div>
                            <span class="text-sm font-medium text-gray-900">Mapel Paralel / Multi-Guru</span>
                            <p class="text-xs text-gray-500">Centang jika mapel ini (misal: Agama) diajarkan serentak oleh semua guru yang ditugaskan di kelas yang sama.</p>
                        </div>
                    </label>
                </div>
                
                @if($is_parallel)
                <div class="mt-3 p-3 bg-blue-50/50 rounded-lg border border-blue-100">
                    <label class="label-field text-blue-900">Nama Kelompok Paralel <span class="text-red-500">*</span></label>
                    <input wire:model="kelompok_paralel" type="text" class="input-field border-blue-200 focus:border-blue-400 focus:ring-blue-400" placeholder="Contoh: Agama, Pilihan Lintas Minat">
                    <p class="text-[11px] text-blue-600/80 mt-1.5 leading-relaxed">Mapel dengan nama kelompok yang <strong>sama persis</strong> akan disatukan ke dalam 1 blok waktu yang sama.</p>
                    @error('kelompok_paralel') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                @endif
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-outline text-sm">Batal</button>
                    <button type="submit" class="btn-primary text-sm">{{ $editingId ? 'Update' : 'Simpan' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
