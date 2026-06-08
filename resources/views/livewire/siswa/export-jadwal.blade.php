<div class="max-w-4xl mx-auto space-y-6">
    <div class="card">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-900">Ekspor Jadwal Pelajaran (PDF)</h2>
            <p class="text-sm text-gray-500 mt-1">Pilih jenis data dan item yang ingin Anda ekspor ke format PDF.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Pilihan Jenis Ekspor -->
            <div class="md:col-span-1 space-y-4">
                <label class="label-field block font-medium">Jenis Ekspor</label>
                
                <div class="space-y-3">
                    <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 transition-colors {{ $exportType === 'kelas' ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-gray-200' }}">
                        <input type="radio" wire:model.live="exportType" value="kelas" class="w-4 h-4 text-primary focus:ring-primary">
                        <div>
                            <span class="block text-sm font-semibold text-gray-900">Per Kelas</span>
                            <span class="block text-xs text-gray-500">Ekspor jadwal untuk siswa di kelas</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 transition-colors {{ $exportType === 'guru' ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-gray-200' }}">
                        <input type="radio" wire:model.live="exportType" value="guru" class="w-4 h-4 text-primary focus:ring-primary">
                        <div>
                            <span class="block text-sm font-semibold text-gray-900">Per Guru</span>
                            <span class="block text-xs text-gray-500">Ekspor jadwal mengajar guru</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 transition-colors {{ $exportType === 'mapel' ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-gray-200' }}">
                        <input type="radio" wire:model.live="exportType" value="mapel" class="w-4 h-4 text-primary focus:ring-primary">
                        <div>
                            <span class="block text-sm font-semibold text-gray-900">Per Mata Pelajaran</span>
                            <span class="block text-xs text-gray-500">Jadwal spesifik suatu mapel</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Pemilihan Data -->
            <div class="md:col-span-2">
                <label class="label-field block font-medium mb-3">
                    Pilih {{ ucfirst($exportType) }}
                    <span class="text-xs font-normal text-gray-500 ml-1">(Bisa pilih lebih dari satu)</span>
                </label>

                @error('selectedIds')
                    <div class="mb-3 p-3 bg-red-50 text-red-600 text-sm rounded-lg border border-red-100 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        {{ $message }}
                    </div>
                @enderror

                <div class="bg-gray-50 border border-gray-200 rounded-xl max-h-96 overflow-y-auto p-4 shadow-inner">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @forelse($list as $item)
                            <label class="flex items-start gap-3 p-3 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-primary/50 hover:shadow-sm transition-all has-[:checked]:border-primary has-[:checked]:ring-1 has-[:checked]:ring-primary">
                                <div class="mt-0.5">
                                    <input type="checkbox" wire:model="selectedIds" value="{{ $item->id }}" class="w-4 h-4 rounded text-primary focus:ring-primary border-gray-300">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <span class="block text-sm font-medium text-gray-900 truncate">{{ $item->nama }}</span>
                                    @if($exportType === 'kelas')
                                        <span class="block text-xs text-gray-500">{{ $item->tingkat->nama }} - {{ $item->jurusan->nama ?? 'Umum' }}</span>
                                    @elseif($exportType === 'guru')
                                        <span class="block text-xs text-gray-500">NIP: {{ $item->nip ?? '-' }}</span>
                                    @elseif($exportType === 'mapel')
                                        <span class="block text-xs text-gray-500">Kode: {{ $item->kode }}</span>
                                    @endif
                                </div>
                            </label>
                        @empty
                            <div class="col-span-2 text-center py-8 text-gray-500 text-sm">
                                Tidak ada data {{ $exportType }} yang tersedia.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('selectedIds', [])" class="btn-outline text-sm">Reset Pilihan</button>
                    <button wire:click="download" wire:loading.attr="disabled" class="btn-primary text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Ekspor PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
