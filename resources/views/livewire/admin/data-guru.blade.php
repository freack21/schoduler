<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="relative flex-1 max-w-md">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari guru..." class="input-field pl-10">
        </div>
        <button wire:click="openCreateModal" class="btn-primary flex items-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah Guru
        </button>
    </div>

    {{-- Table --}}
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
                        <th class="px-6 py-3">NIP</th>
                        <th class="px-6 py-3">Mapel yang Diajar</th>
                        <th class="px-6 py-3 cursor-pointer hover:bg-gray-100/50 select-none" wire:click="sort('beban_mengajar')">
                            <div class="flex items-center gap-2">
                                Beban Mengajar
                                @if($sortBy === 'beban_mengajar')
                                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDir === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($guruList as $i => $guru)
                        <tr class="table-row" wire:key="guru-{{ $guru->id }}">
                            <td class="px-6 py-4 text-gray-500">{{ $guruList->firstItem() + $i }}</td>
                            <td class="px-6 py-4 font-medium text-gray-900">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold">
                                        {{ strtoupper(substr($guru->user->nama_lengkap, 0, 1)) }}
                                    </div>
                                    {{ $guru->user->nama_lengkap }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500 font-mono text-xs">{{ $guru->user->id }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($guru->guruMapel->pluck('mapel.nama')->unique() as $mapel)
                                        <span class="badge bg-blue-50 text-blue-700">{{ $mapel }}</span>
                                    @endforeach
                                    @if($guru->guruMapel->isEmpty())
                                        <span class="text-gray-400 text-xs italic">Belum ada</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $totalJam = $guru->guruMapel->sum('mapel.jam_per_minggu');
                                    $isOverload = $totalJam > 40;
                                @endphp
                                <div class="flex items-center gap-2">
                                    <span class="font-bold {{ $isOverload ? 'text-red-600' : 'text-gray-700' }}">{{ $totalJam }} Jam</span>
                                    @if($isOverload)
                                        <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" title="Melebihi batas ideal (40 jam)"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="openAssignModal({{ $guru->id }})" class="p-1.5 rounded-lg hover:bg-amber-50 text-amber-600 transition-colors cursor-pointer" title="Assign Mapel">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-6.06a4.5 4.5 0 00-6.364 0l-4.5 4.5a4.5 4.5 0 006.364 6.364l1.757-1.757"/></svg>
                                    </button>
                                    <button wire:click="openEditModal({{ $guru->id }})" class="p-1.5 rounded-lg hover:bg-blue-50 text-blue-600 transition-colors cursor-pointer" title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $guru->id }})" class="p-1.5 rounded-lg hover:bg-red-50 text-red-500 transition-colors cursor-pointer" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">Belum ada data guru.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($guruList->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">{{ $guruList->links() }}</div>
        @endif
    </div>

    {{-- Create/Edit Modal --}}
    @if($showModal)
    <div class="modal-overlay" wire:click.self="$set('showModal', false)">
        <div class="modal-content" @click.stop>
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit Guru' : 'Tambah Guru Baru' }}</h3>
            </div>
            <form wire:submit="save" class="p-6 space-y-4">
                <div>
                    <label class="label-field">NIP</label>
                    <input wire:model="nip" type="text" class="input-field" placeholder="Masukkan NIP" @if($editingId) disabled @endif>
                    @error('nip') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Nama Lengkap</label>
                    <input wire:model="nama_lengkap" type="text" class="input-field" placeholder="Masukkan nama lengkap">
                    @error('nama_lengkap') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Password {{ $editingId ? '(kosongkan jika tidak diubah)' : '' }}</label>
                    <input wire:model="password" type="password" class="input-field" placeholder="Masukkan password">
                    @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-outline text-sm">Batal</button>
                    <button type="submit" class="btn-primary text-sm">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Simpan' }}</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Assign Mapel Modal --}}
    @if($showAssignModal)
    <div class="modal-overlay" wire:click.self="$set('showAssignModal', false)">
        <div class="modal-content max-w-2xl" @click.stop>
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Assign Mapel — {{ $assignGuruName }}</h3>
            </div>
            <div class="p-6">
                {{-- Add Assignment Form --}}
                <div class="flex flex-col sm:flex-row gap-3 mb-6">
                    <select wire:model="selectedMapelId" class="input-field flex-1">
                        <option value="0">Pilih Mapel</option>
                        @foreach($mapelList as $mapel)
                            <option value="{{ $mapel->id }}">{{ $mapel->nama }} ({{ $mapel->kode }})</option>
                        @endforeach
                    </select>
                    <button wire:click="addAssignment" class="btn-primary text-sm whitespace-nowrap">+ Tambah</button>
                </div>
                @error('selectedMapelId') <p class="text-xs text-red-500 mb-3">{{ $message }}</p> @enderror

                {{-- Assignments List --}}
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @forelse($assignments as $assignment)
                        <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-2.5">
                            <div class="flex items-center gap-3">
                                <span class="badge bg-blue-50 text-blue-700">{{ $assignment['mapel'] }}</span>
                            </div>
                            <button wire:click="removeAssignment({{ $assignment['id'] }})" class="text-red-400 hover:text-red-600 transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-4 italic">Belum ada mapel yang di-assign.</p>
                    @endforelse
                </div>

                <div class="flex justify-end pt-4 mt-4 border-t border-gray-100">
                    <button wire:click="$set('showAssignModal', false)" class="btn-outline text-sm">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
