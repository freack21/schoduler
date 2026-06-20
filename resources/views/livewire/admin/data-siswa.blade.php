<div>
    @if(!$selectedKelasId)
        {{-- VIEW A: CLASS CARDS GRID --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Data Kelas & Siswa</h2>
                <p class="text-sm text-gray-500 mt-1">Pilih kelas di bawah ini untuk melihat dan mengelola daftar siswa.</p>
            </div>
            <button wire:click="openCreateKelasModal" class="btn-primary flex items-center gap-2 text-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Tambah Kelas
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @forelse($kelasList as $kelas)
                <div class="group relative card !p-5 hover:shadow-lg hover:border-primary/30 transition-all duration-300 flex flex-col justify-between border border-gray-200 cursor-pointer min-h-[140px] rounded-2xl bg-white"
                     wire:click="selectKelas({{ $kelas->id }})">
                    <!-- Class Action Badges (Top-Right) -->
                    <div class="absolute top-4 right-4 flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                        <button wire:click="openEditKelasModal({{ $kelas->id }})" class="p-1 rounded bg-gray-50 hover:bg-blue-50 text-blue-600 transition-colors" title="Edit Kelas">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                        </button>
                        <button wire:click="confirmDeleteKelas({{ $kelas->id }})" class="p-1 rounded bg-gray-50 hover:bg-red-50 text-red-600 transition-colors" title="Hapus Kelas">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>

                    <div>
                        <span class="badge bg-primary/10 text-primary mb-2 inline-block">
                            {{ $kelas->tingkat->nama }} • {{ $kelas->jurusan->nama ?? 'Umum' }}
                        </span>
                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-primary transition-colors">Kelas {{ $kelas->nama }}</h3>
                    </div>

                    <div class="mt-4 flex items-center justify-between text-xs text-gray-500">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $kelas->siswa_count }} Siswa
                        </span>
                        <span class="text-primary font-semibold group-hover:underline flex items-center gap-0.5">
                            Detail
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </div>
                </div>
            @empty
                <div class="col-span-full card py-12 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21h10.5V6.75H6.75V21zm0 0h10.5V6.75H6.75V21z"/></svg>
                    <p class="text-sm font-medium">Belum ada data kelas. Tambahkan kelas pertama Anda.</p>
                </div>
            @endforelse
        </div>
    @else
        {{-- VIEW B: SCOPED STUDENT TABLE --}}
        <div class="mb-6 flex items-center gap-3">
            <button wire:click="selectKelas(null)" class="btn-outline !py-1.5 !px-3 text-xs flex items-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Kembali ke Kelas
            </button>
            <div class="h-6 w-px bg-gray-300"></div>
            <div>
                <span class="text-xs text-gray-500 font-medium">Kelas aktif:</span>
                <span class="badge bg-purple-50 text-purple-700 ml-1.5 text-xs font-semibold">Kelas {{ $activeKelas->nama }}</span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="relative flex-1 max-w-md">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari siswa di kelas ini..." class="input-field pl-10">
            </div>

            <div class="flex items-center gap-3">
                <!-- Import Excel -->
                <label class="btn-outline flex items-center gap-2 text-sm cursor-pointer py-2 px-3">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Import Excel
                    <input type="file" wire:model="excelFile" class="hidden" accept=".xlsx,.xls,.csv" wire:change="importExcel">
                </label>
                <div wire:loading wire:target="excelFile" class="text-xs text-gray-500 animate-pulse">Mengunggah...</div>

                <button wire:click="openCreateModal" class="btn-primary flex items-center gap-2 text-sm cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Tambah Siswa
                </button>
            </div>
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
                            <th class="px-6 py-3">Kelas</th>
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
                            <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">Belum ada data siswa di kelas ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($siswaList->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">{{ $siswaList->links() }}</div>
            @endif
        </div>
    @endif

    {{-- CLASS CREATE/EDIT MODAL --}}
    @if($showKelasModal)
    <div class="modal-overlay" wire:click.self="$set('showKelasModal', false)">
        <div class="modal-content animate-fadeIn" @click.stop>
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">{{ $editingKelasId ? 'Edit Kelas' : 'Tambah Kelas Baru' }}</h3>
            </div>
            <form wire:submit.prevent="saveKelas" class="p-6 space-y-4">
                <div>
                    <label class="label-field">Nama Kelas</label>
                    <input wire:model="kelas_nama" type="text" class="input-field" placeholder="Contoh: X IPA 1, XI IPS 2, dll">
                    @error('kelas_nama') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Tingkat Kelas</label>
                    <select wire:model="kelas_tingkat_id" class="input-field">
                        <option value="">-- Pilih Tingkat --</option>
                        @foreach($tingkatList as $tingkat)
                            <option value="{{ $tingkat->id }}">Tingkat {{ $tingkat->nama }}</option>
                        @endforeach
                    </select>
                    @error('kelas_tingkat_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Jurusan</label>
                    <select wire:model="kelas_jurusan_id" class="input-field">
                        <option value="">Umum (Tanpa Jurusan)</option>
                        @foreach($jurusanList as $jurusan)
                            <option value="{{ $jurusan->id }}">{{ $jurusan->nama }}</option>
                        @endforeach
                    </select>
                    @error('kelas_jurusan_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-50">
                    <button type="button" wire:click="$set('showKelasModal', false)" class="btn-outline text-sm">Batal</button>
                    <button type="submit" class="btn-primary text-sm">Simpan Kelas</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- STUDENT CREATE/EDIT MODAL --}}
    @if($showModal)
    <div class="modal-overlay" wire:click.self="$set('showModal', false)">
        <div class="modal-content animate-fadeIn" @click.stop>
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
                    <select wire:model="kelas_id" class="input-field" disabled>
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
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-50">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-outline text-sm">Batal</button>
                    <button type="submit" class="btn-primary text-sm">{{ $editingId ? 'Update' : 'Simpan' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
