<div>
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Atur jam pelajaran sesuai kebutuhan sekolah</p>
        <button wire:click="openCreateModal" class="btn-primary flex items-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah Jam
        </button>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @forelse($jamList as $jam)
            <div class="card hover:shadow-md transition-shadow duration-200 text-center {{ $jam->is_istirahat ? '!bg-amber-50 !border-amber-200' : '' }}" wire:key="jam-{{ $jam->id }}">
                @if($jam->is_istirahat)
                    <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-lg mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.871c1.355 0 2.697.056 4.024.166C17.155 8.51 18 9.473 18 10.608v2.513M15 8.25v-1.5m-6 1.5v-1.5m12 9.75l-1.5.75a3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0L3 16.5m15-3.379a48.474 48.474 0 00-6-.371c-2.032 0-4.034.126-6 .371m12 0c.39.049.777.102 1.163.16 1.07.16 1.837 1.094 1.837 2.175v5.169c0 .621-.504 1.125-1.125 1.125H4.125A1.125 1.125 0 013 20.625v-5.17c0-1.08.768-2.014 1.837-2.174A47.78 47.78 0 016 13.12M12.265 3.11a.375.375 0 11-.53 0L12 2.845l.265.265z"/></svg>
                    </div>
                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-amber-200 text-amber-700 rounded-full mb-2">Istirahat</span>
                @else
                    <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center text-lg font-bold mx-auto mb-3">
                        {{ $jam->jam_ke }}
                    </div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Jam ke-{{ $jam->jam_ke }}</p>
                @endif
                <p class="text-lg font-semibold {{ $jam->is_istirahat ? 'text-amber-800' : 'text-gray-900' }}">
                    {{ substr($jam->jam_mulai, 0, 5) }} - {{ substr($jam->jam_selesai, 0, 5) }}
                </p>
                <div class="flex items-center justify-center gap-3 mt-4 pt-3 border-t {{ $jam->is_istirahat ? 'border-amber-200' : 'border-gray-100' }}">
                    <button wire:click="openEditModal({{ $jam->id }})" class="text-xs text-blue-600 hover:underline cursor-pointer">Edit</button>
                    <span class="text-gray-300">•</span>
                    <button wire:click="confirmDelete({{ $jam->id }})" class="text-xs text-red-500 hover:underline cursor-pointer">Hapus</button>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-gray-400">Belum ada jam pelajaran. Tambahkan terlebih dahulu.</div>
        @endforelse
    </div>

    @if($showModal)
    <div class="modal-overlay" wire:click.self="$set('showModal', false)">
        <div class="modal-content max-w-sm" @click.stop>
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit Jam Pelajaran' : 'Tambah Jam Pelajaran' }}</h3>
            </div>
            <form wire:submit="save" class="p-6 space-y-4">
                <div>
                    <label class="label-field">Jam Ke</label>
                    <input wire:model="jam_ke" type="number" min="1" class="input-field">
                    @error('jam_ke') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label-field">Mulai</label>
                        <input wire:model="jam_mulai" type="time" class="input-field">
                        @error('jam_mulai') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label-field">Selesai</label>
                        <input wire:model="jam_selesai" type="time" class="input-field">
                        @error('jam_selesai') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Istirahat Toggle --}}
                <div class="flex items-center justify-between p-3 rounded-xl {{ $is_istirahat ? 'bg-amber-50 border border-amber-200' : 'bg-gray-50 border border-gray-200' }}">
                    <div>
                        <p class="text-sm font-medium {{ $is_istirahat ? 'text-amber-800' : 'text-gray-700' }}">Jam Istirahat</p>
                        <p class="text-xs {{ $is_istirahat ? 'text-amber-600' : 'text-gray-400' }}">Slot ini tidak akan diisi mapel oleh GA</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="is_istirahat" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                    </label>
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
