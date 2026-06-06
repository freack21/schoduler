<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="relative flex-1 max-w-md">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari siswa..." class="input-field pl-10">
        </div>
        <button wire:click="openCreateModal" class="btn-primary flex items-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah Siswa
        </button>
    </div>

    <div class="card !p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="table-header">
                    <tr>
                        <th class="px-6 py-3">No</th>
                        <th class="px-6 py-3 cursor-pointer hover:bg-gray-100/50 select-none" wire:click="sort('nama_lengkap')">
                            <div class="flex items-center gap-2">
                                Nama
                                @if($sortBy === 'nama_lengkap')
                                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDir === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3">NISN</th>
                        <th class="px-6 py-3 cursor-pointer hover:bg-gray-100/50 select-none" wire:click="sort('kelas')">
                            <div class="flex items-center gap-2">
                                Kelas
                                @if($sortBy === 'kelas')
                                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDir === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($siswaList as $i => $siswa)
                        <tr class="table-row" wire:key="siswa-{{ $siswa->id }}">
                            <td class="px-6 py-4 text-gray-500">{{ $siswaList->firstItem() + $i }}</td>
                            <td class="px-6 py-4 font-medium text-gray-900">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-green-50 text-green-600 flex items-center justify-center text-xs font-bold">
                                        {{ strtoupper(substr($siswa->user->nama_lengkap, 0, 1)) }}
                                    </div>
                                    {{ $siswa->user->nama_lengkap }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500 font-mono text-xs">{{ $siswa->user->id }}</td>
                            <td class="px-6 py-4"><span class="badge bg-purple-50 text-purple-700">{{ $siswa->kelas->nama }}</span></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="openEditModal({{ $siswa->id }})" class="p-1.5 rounded-lg hover:bg-blue-50 text-blue-600 transition-colors cursor-pointer" title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $siswa->id }})" class="p-1.5 rounded-lg hover:bg-red-50 text-red-500 transition-colors cursor-pointer" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">Belum ada data siswa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($siswaList->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">{{ $siswaList->links() }}</div>
        @endif
    </div>

    @if($showModal)
    <div class="modal-overlay" wire:click.self="$set('showModal', false)">
        <div class="modal-content" @click.stop>
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit Siswa' : 'Tambah Siswa Baru' }}</h3>
            </div>
            <form wire:submit="save" class="p-6 space-y-4">
                <div>
                    <label class="label-field">NISN</label>
                    <input wire:model="nisn" type="text" class="input-field" placeholder="Masukkan NISN" @if($editingId) disabled @endif>
                    @error('nisn') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Nama Lengkap</label>
                    <input wire:model="nama_lengkap" type="text" class="input-field" placeholder="Masukkan nama lengkap">
                    @error('nama_lengkap') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Kelas</label>
                    <select wire:model="kelas_id" class="input-field">
                        <option value="0">Pilih Kelas</option>
                        @foreach($kelasList as $kelas)
                            <option value="{{ $kelas->id }}">{{ $kelas->nama }}</option>
                        @endforeach
                    </select>
                    @error('kelas_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Password {{ $editingId ? '(kosongkan jika tidak diubah)' : '' }}</label>
                    <input wire:model="password" type="password" class="input-field" placeholder="Masukkan password">
                    @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-outline text-sm">Batal</button>
                    <button type="submit" class="btn-primary text-sm">{{ $editingId ? 'Update' : 'Simpan' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
