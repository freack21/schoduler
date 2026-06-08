<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Jadwal Per Mata Pelajaran</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; }
        .page-break { page-break-after: always; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; color: #1e3a8a; }
        .header p { margin: 5px 0 0; font-size: 12px; color: #64748b; }
        .title { font-size: 14px; font-weight: bold; margin-bottom: 10px; background-color: #f1f5f9; padding: 8px; border-left: 4px solid #3b82f6; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: center; vertical-align: top; }
        th { background-color: #e2e8f0; font-weight: bold; color: #1e293b; }
        .istirahat { background-color: #fef08a; font-weight: bold; color: #854d0e; }
        .kelas { font-weight: bold; color: #0f172a; margin-bottom: 4px; display: block; font-size: 11px; }
        .guru { color: #475569; font-size: 10px; }
        .jam { font-size: 10px; color: #64748b; }
        .kosong { color: #cbd5e1; font-style: italic; }
    </style>
</head>
<body>

@foreach($entities as $index => $mapel)
    @if($index > 0)
        <div class="page-break"></div>
    @endif
    
    <div class="header">
        <h1>Jadwal Pelajaran</h1>
        <p>SMA Negeri 1 Tapung Hulu - Tahun Ajaran 2026/2027</p>
    </div>

    <div class="title">Jadwal Mata Pelajaran: {{ $mapel->nama }}</div>

    <table>
        <thead>
            <tr>
                <th width="8%">Jam Ke</th>
                <th width="12%">Waktu</th>
                @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $hari)
                    <th width="13%">{{ $hari }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $maxJam = 0;
                foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $hari) {
                    if(isset($jamPelajaran[$hari])) {
                        $maxJam = max($maxJam, count($jamPelajaran[$hari]));
                    }
                }
            @endphp

            @for($i = 0; $i < $maxJam; $i++)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        @php
                            $waktuAcuan = '-';
                            foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $hari) {
                                if(isset($jamPelajaran[$hari]) && isset($jamPelajaran[$hari][$i])) {
                                    $waktuAcuan = $jamPelajaran[$hari][$i]->rentang;
                                    break;
                                }
                            }
                        @endphp
                        <span class="jam">{{ $waktuAcuan }}</span>
                    </td>
                    
                    @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $hari)
                        @php
                            $jam = isset($jamPelajaran[$hari]) && isset($jamPelajaran[$hari][$i]) ? $jamPelajaran[$hari][$i] : null;
                        @endphp
                        
                        @if($jam)
                            @if($jam->is_istirahat)
                                <td class="istirahat">ISTIRAHAT</td>
                            @else
                                <td>
                                    @if(isset($jadwalGrouped[$mapel->id][$hari][$jam->id]))
                                        @foreach($jadwalGrouped[$mapel->id][$hari][$jam->id] as $j)
                                            <div style="{{ !$loop->last ? 'margin-bottom: 5px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 5px;' : '' }}">
                                                <span class="kelas">Kelas: {{ $j->kelas->nama }}</span>
                                                <span class="guru">{{ $j->guru->nama }}</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="kosong">-</span>
                                    @endif
                                </td>
                            @endif
                        @else
                            <td style="background-color: #f8fafc;"></td>
                        @endif
                    @endforeach
                </tr>
            @endfor
        </tbody>
    </table>
@endforeach

</body>
</html>
