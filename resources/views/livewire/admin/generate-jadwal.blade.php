<div @if($status === 'running' || $status === 'starting') wire:poll.1s="refreshStatus" @endif>
    {{-- Header --}}
    <div class="card mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Algoritma Genetika - Penjadwalan Otomatis</h3>
                <p class="text-sm text-gray-500 mt-1">Generate jadwal pelajaran secara otomatis dengan persebaran optimal</p>
            </div>
            <div class="flex gap-2">
                @if($status === 'idle' || $status === 'done' || $status === 'error')
                    <button x-on:click="swalConfirm({ title: 'Generate Jadwal Baru?', text: 'Jadwal lama akan dihapus dan diganti dengan jadwal baru.', confirmText: 'Ya, Generate!' }).then(r => { if(r.isConfirmed) $wire.generate() })" class="btn-primary flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Generate Jadwal
                    </button>
                @endif
                @if($status === 'done')
                    <button wire:click="resetGenerate" class="btn-outline flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/></svg>
                        Reset
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Settings: Hari Aktif --}}
    @if($status === 'idle' || $status === 'done' || $status === 'error')
    <div class="card mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h4 class="font-semibold text-gray-800">Pengaturan Hari Aktif</h4>
                <p class="text-xs text-gray-500 mt-0.5">Pilih hari yang digunakan untuk penjadwalan. Default: Senin-Jumat</p>
            </div>
            <button wire:click="saveHariAktif" class="btn-primary text-sm">Simpan</button>
        </div>
        <div class="flex flex-wrap gap-3">
            @foreach($allHariOptions as $hariOption)
                <label class="flex items-center gap-2 px-4 py-2.5 rounded-xl border cursor-pointer transition-all duration-200
                    {{ in_array($hariOption, $selectedHari) ? 'bg-primary text-white border-primary shadow-md' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100' }}">
                    <input type="checkbox" wire:model.live="selectedHari" value="{{ $hariOption }}" class="sr-only">
                    <svg class="w-4 h-4 {{ in_array($hariOption, $selectedHari) ? '' : 'opacity-0' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    <span class="text-sm font-medium">{{ $hariOption }}</span>
                </label>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Progress --}}
    @if($status === 'running' || $status === 'starting')
    <div class="card mb-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></div>
            <span class="text-sm font-medium text-gray-700">Sedang memproses...</span>
        </div>

        {{-- Progress Bar --}}
        <div class="w-full bg-gray-100 rounded-full h-4 mb-4 overflow-hidden">
            <div class="bg-gradient-to-r from-primary to-primary-light h-full rounded-full transition-all duration-500 flex items-center justify-center" style="width: {{ min(($generation / $maxGenerations) * 100, 100) }}%">
                @if($generation > ($maxGenerations * 0.05))
                    <span class="text-[10px] text-white font-bold">{{ number_format(($generation / $maxGenerations) * 100, 0) }}%</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500">Generasi</p>
                <p class="text-xl font-bold text-primary">{{ $generation }}<span class="text-sm text-gray-400">/{{ $maxGenerations }}</span></p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500">Fitness</p>
                <p class="text-xl font-bold {{ $fitness >= 1 ? 'text-green-600' : 'text-amber-600' }}">{{ $fitness }}</p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500">Bentrok</p>
                <p class="text-xl font-bold {{ $violations === 0 ? 'text-green-600' : 'text-red-500' }}">{{ $violations }}</p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500">Persebaran</p>
                <p class="text-xl font-bold {{ $distViolations === 0 ? 'text-green-600' : 'text-amber-500' }}">{{ $distViolations }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Result Message --}}
    @if($status === 'done' && $message)
    <div class="mb-6 p-4 rounded-xl {{ $violations === 0 && $distViolations === 0 ? 'bg-green-50 border border-green-200' : ($violations === 0 ? 'bg-blue-50 border border-blue-200' : 'bg-amber-50 border border-amber-200') }}">
        <div class="flex items-center gap-3">
            @if($violations === 0 && $distViolations === 0)
                <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm font-medium text-green-800">{{ $message }}</p>
            @elseif($violations === 0)
                <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                <p class="text-sm font-medium text-blue-800">{{ $message }}</p>
            @else
                <svg class="w-6 h-6 text-amber-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                <p class="text-sm font-medium text-amber-800">{{ $message }}</p>
            @endif
        </div>
    </div>
    @endif

    @if($status === 'error')
    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200">
        <p class="text-sm font-medium text-red-800">❌ {{ $message }}</p>
    </div>
    @endif

    {{-- Schedule Results --}}
    @if($showResult && !empty($jadwalData))
    <div class="space-y-6">
        @foreach($jadwalData as $data)
            <div class="card !p-0 overflow-hidden">
                <div class="px-6 py-3 bg-primary text-white font-semibold text-sm">
                    📋 Jadwal Kelas {{ $data['kelas'] }}
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="table-header">
                            <tr>
                                <th class="px-3 py-2 text-center">Jam</th>
                                @foreach($hari as $h)
                                    <th class="px-3 py-2 text-center">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jamList as $jam)
                                @if($jam->is_istirahat)
                                    <tr class="bg-amber-50">
                                        <td class="px-3 py-2 text-center font-medium bg-amber-100 text-amber-700">
                                            <div class="text-[10px] uppercase tracking-wider font-bold">Istirahat</div>
                                            <div class="text-[10px]">{{ substr($jam->jam_mulai,0,5) }}-{{ substr($jam->jam_selesai,0,5) }}</div>
                                        </td>
                                        <td colspan="{{ count($hari) }}" class="px-3 py-2 text-center text-amber-600 text-xs font-medium">
                                            ☕ Istirahat
                                        </td>
                                    </tr>
                                @else
                                    <tr class="table-row">
                                        <td class="px-3 py-2 text-center font-medium bg-gray-50">
                                            <div>{{ $jam->jam_ke }}</div>
                                            <div class="text-[10px] text-gray-400">{{ substr($jam->jam_mulai,0,5) }}</div>
                                        </td>
                                        @foreach($hari as $h)
                                            <td class="px-2 py-1.5 text-center">
                                                @if(isset($data['matrix'][$h][$jam->id]) && $data['matrix'][$h][$jam->id])
                                                    @php $cell = $data['matrix'][$h][$jam->id]; @endphp
                                                    <div class="bg-blue-50 rounded-lg px-2 py-1.5">
                                                        <div class="font-bold text-blue-700">
                                                            {{ $cell['mapel'] }}
                                                            @if($cell['total'] > 1)
                                                                <span class="text-[10px] font-normal text-blue-400">(x{{ $cell['seq'] }})</span>
                                                            @endif
                                                        </div>
                                                        <div class="text-[10px] text-gray-500 truncate max-w-[80px]">{{ $cell['guru'] }}</div>
                                                    </div>
                                                @else
                                                    <span class="text-gray-300">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
    @endif
</div>
