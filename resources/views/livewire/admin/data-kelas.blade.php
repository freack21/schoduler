<div>
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Kelola data kelas dan tingkatan</p>
        <button wire:click="openCreateModal" class="btn-primary flex items-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah Kelas
        </button>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($kelasList as $kelas)
            <div class="card hover:shadow-md transition-shadow duration-200" wire:key="kelas-{{ $kelas->id }}">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-bold text-gray-900">{{ $kelas->nama }}</h3>
                    <span class="badge bg-primary/10 text-primary">{{ $kelas->tingkat->nama }}</span>
                </div>
                <p class="text-sm text-gray-500 mb-4">{{ $kelas->siswa->count() }} siswa</p>
                <div class="flex items-center gap-2">
                    <button wire:click="openEditModal({{ $kelas->id }})" class="text-xs text-blue-600 hover:underline cursor-pointer">Edit</button>
                    <span class="text-gray-300">•</span>
                    <button wire:click="confirmDelete({{ $kelas->id }})" class="text-xs text-red-500 hover:underline cursor-pointer">Hapus</button>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-gray-400">Belum ada data kelas.</div>
        @endforelse
    </div>

    @if($showModal)
    <div class="modal-overlay" wire:click.self="$set('showModal', false)">
        <div class="modal-content" @click.stop>
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit Kelas' : 'Tambah Kelas Baru' }}</h3>
            </div>
            <form wire:submit="save" class="p-6 space-y-4">
                <div>
                    <label class="label-field">Nama Kelas</label>
                    <input wire:model="nama" type="text" class="input-field" placeholder="Contoh: X-1, XI-IPA-1">
                    @error('nama') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
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
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-outline text-sm">Batal</button>
                    <button type="submit" class="btn-primary text-sm">{{ $editingId ? 'Update' : 'Simpan' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
