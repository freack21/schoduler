<div wire:poll.2s.keep-alive="refreshStatus" class="space-y-6">
    {{-- Header --}}
    <div wire:key="header-card" class="card bg-gradient-to-br from-white to-gray-50/50 shadow-sm border border-gray-100/50 mb-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                    Algoritma Genetika - Penjadwalan Otomatis
                </h3>
                <p class="text-sm text-gray-500 mt-1 ml-8">Generate jadwal pelajaran secara otomatis dengan persebaran optimal menggunakan pendekatan *Genetic Algorithm*.</p>
            </div>
            <div class="flex gap-3">
                @if($status === 'idle' || $status === 'done' || $status === 'error')
                    <button x-on:click="swalConfirm({ title: 'Generate Jadwal Baru?', text: 'Jadwal lama akan dihapus dan diganti dengan jadwal baru.', confirmText: 'Ya, Generate!' }).then(r => { if(r.isConfirmed) $wire.generate() })" class="btn-primary flex items-center gap-2 shadow-lg shadow-primary/20 hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Mulai Generate
                    </button>
                @endif
                @if($status === 'done')
                    <button wire:click="resetGenerate" class="btn-outline flex items-center gap-2 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/></svg>
                        Reset Status
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Settings: Hari Aktif --}}
    @if($status === 'idle' || $status === 'done' || $status === 'error')
    <div wire:key="settings-card" class="card bg-white/70 backdrop-blur-md border border-gray-200/50 mb-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h4 class="font-semibold text-gray-800">Pengaturan Hari Aktif</h4>
                <p class="text-sm text-gray-500 mt-0.5">Pilih hari yang digunakan untuk penjadwalan. Default: Senin - Jumat</p>
            </div>
            <button wire:click="saveHariAktif" class="btn-primary text-sm px-6">Simpan</button>
        </div>
        <div class="flex flex-wrap gap-3">
            @foreach($allHariOptions as $hariOption)
                <label class="flex items-center gap-2 px-5 py-3 rounded-xl border cursor-pointer transition-all duration-300
                    {{ in_array($hariOption, $selectedHari) ? 'bg-primary text-white border-primary shadow-lg shadow-primary/20 scale-105' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:border-gray-300' }}">
                    <input type="checkbox" wire:model.live="selectedHari" value="{{ $hariOption }}" class="sr-only">
                    <svg class="w-4 h-4 transition-opacity duration-300 {{ in_array($hariOption, $selectedHari) ? 'opacity-100' : 'opacity-0 hidden' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    <span class="text-sm font-semibold tracking-wide">{{ $hariOption }}</span>
                </label>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Progress Dashboard --}}
    @if($status === 'running' || $status === 'starting')
    <div wire:key="progress-card" class="card relative overflow-hidden border border-primary/20 bg-gradient-to-b from-white to-primary/5 shadow-xl shadow-primary/5 mb-6">
        {{-- Animated background --}}
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMzcsIDk5LCAyMzUsIDAuMSkiLz48L3N2Zz4=')] [background-size:20px_20px] opacity-50"></div>
        
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="relative flex h-4 w-4">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-4 w-4 bg-primary"></span>
                    </div>
                    <span class="text-sm font-bold text-primary tracking-widest uppercase">Memproses Jadwal...</span>
                </div>
                <div class="text-xs font-mono bg-white/60 px-3 py-1 rounded-full text-gray-500 border border-gray-200">
                    Engine: Genetic Algorithm
                </div>
            </div>

            {{-- Sophisticated Progress Bar --}}
            <div class="mb-8">
                <div class="flex justify-between text-xs font-semibold text-gray-500 mb-2">
                    <span>Progres Evolusi</span>
                    <span>{{ $maxGenerations > 0 ? min(100, floor(($generation / $maxGenerations) * 100)) : 0 }}%</span>
                </div>
                <div class="w-full bg-gray-200/50 rounded-full h-4 overflow-hidden shadow-inner relative">
                    <div class="absolute top-0 left-0 bottom-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTAgNDBsNDAtNDBIMjBMMCAyMHoiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4yKSIvPjxwYXRoIGQ9Ik00MCA0MEgyMEwwIDQwaDIwbDIwLTIweiIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjIpIi8+PC9zdmc+')] animate-[progress_2s_linear_infinite] w-full h-full z-10 pointer-events-none"></div>
                    <div class="bg-gradient-to-r from-primary to-secondary h-full rounded-full transition-all duration-700 ease-out relative z-0" style="width: {{ $maxGenerations > 0 ? min(($generation / $maxGenerations) * 100, 100) : 0 }}%">
                    </div>
                </div>
            </div>

            {{-- Metrics Dashboard --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white/80 backdrop-blur-sm p-4 rounded-2xl border border-white/50 shadow-sm flex flex-col items-center justify-center relative overflow-hidden group hover:scale-105 transition-transform duration-300">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-500/10 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Generasi</p>
                    <p class="text-3xl font-black text-gray-800">{{ $generation }}<span class="text-sm font-medium text-gray-400">/{{ $maxGenerations }}</span></p>
                </div>
                
                <div class="bg-white/80 backdrop-blur-sm p-4 rounded-2xl border border-white/50 shadow-sm flex flex-col items-center justify-center relative overflow-hidden group hover:scale-105 transition-transform duration-300">
                    <div class="absolute -right-4 -top-4 w-16 h-16 {{ $fitness >= 1 ? 'bg-green-500/10' : 'bg-amber-500/10' }} rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Tingkat Kebugaran</p>
                    <p class="text-3xl font-black {{ $fitness >= 1 ? 'text-green-600' : 'text-amber-600' }}">{{ number_format($fitness, 4) }}</p>
                </div>

                <div class="bg-white/80 backdrop-blur-sm p-4 rounded-2xl border border-white/50 shadow-sm flex flex-col items-center justify-center relative overflow-hidden group hover:scale-105 transition-transform duration-300">
                    <div class="absolute -right-4 -top-4 w-16 h-16 {{ $violations === 0 ? 'bg-green-500/10' : 'bg-red-500/10' }} rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Bentrok Guru/Kelas</p>
                    <p class="text-3xl font-black {{ $violations === 0 ? 'text-green-600' : 'text-red-500' }}">{{ $violations }}</p>
                </div>

                <div class="bg-white/80 backdrop-blur-sm p-4 rounded-2xl border border-white/50 shadow-sm flex flex-col items-center justify-center relative overflow-hidden group hover:scale-105 transition-transform duration-300">
                    <div class="absolute -right-4 -top-4 w-16 h-16 {{ $distViolations === 0 ? 'bg-green-500/10' : 'bg-amber-500/10' }} rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Pelanggaran Distribusi</p>
                    <p class="text-3xl font-black {{ $distViolations === 0 ? 'text-green-600' : 'text-amber-500' }}">{{ $distViolations }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Result Message --}}
    @if($status === 'done' && $message)
    <div wire:key="result-card" class="mb-6 p-5 rounded-2xl shadow-lg border {{ $violations === 0 && $distViolations === 0 ? 'bg-gradient-to-r from-green-50 to-emerald-50 border-green-200' : ($violations === 0 ? 'bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200' : 'bg-gradient-to-r from-amber-50 to-orange-50 border-amber-200') }} transform transition-all duration-500 hover:scale-[1.01]">
        <div class="flex items-center gap-4">
            @if($violations === 0 && $distViolations === 0)
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h4 class="text-green-900 font-bold text-lg">Jadwal Sempurna!</h4>
                    <p class="text-sm font-medium text-green-700 mt-0.5">{{ $message }}</p>
                </div>
            @elseif($violations === 0)
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                </div>
                <div>
                    <h4 class="text-blue-900 font-bold text-lg">Jadwal Berhasil Dibuat</h4>
                    <p class="text-sm font-medium text-blue-700 mt-0.5">{{ $message }}</p>
                </div>
            @else
                <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                </div>
                <div>
                    <h4 class="text-amber-900 font-bold text-lg">Perlu Generate Ulang</h4>
                    <p class="text-sm font-medium text-amber-800 mt-0.5">{{ $message }}</p>
                </div>
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
                                                            <span class="text-[10px] font-normal text-blue-400">({{ $cell['seq'] }}/{{ $cell['total'] }})</span>
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
