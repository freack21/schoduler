<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Jadwal Komprehensif</title>
    <style>
        @page {
            size: folio landscape;
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            color: #000;
        }
        /* Header / Kop Surat Placeholder */
        .kop-surat {
            width: 100%;
            border-bottom: 3px solid #000;
            padding-bottom: 5px;
            margin-bottom: 2px;
            text-align: center;
        }
        .kop-surat-inner {
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
        }
        .kop-surat h1 {
            font-size: 14pt;
            margin: 0 0 3px 0;
            font-weight: bold;
        }
        .kop-surat h2 {
            font-size: 16pt;
            margin: 0 0 3px 0;
            font-weight: bold;
        }
        .kop-surat p {
            margin: 0;
            font-size: 9pt;
        }
        .info-grid {
            display: table;
            width: 80%;
            margin: 5px auto 0 auto;
            text-align: left;
            font-size: 9pt;
        }
        .info-row {
            display: table-row;
        }
        .info-cell {
            display: table-cell;
            padding: 2px 10px;
        }
        .akreditasi {
            font-weight: bold;
            font-size: 11pt;
            margin-top: 5px;
        }
        
        .page-break {
            page-break-after: always;
        }

        /* Tabel Jadwal */
        table.jadwal-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.jadwal-table th, table.jadwal-table td {
            border: 1px solid #000;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
        }
        table.jadwal-table th {
            background-color: #b8cce4; /* Blue shade similar to image */
            font-weight: bold;
            font-size: 8pt;
        }
        .col-hari {
            width: 25px;
            font-weight: bold;
        }
        .text-vertical {
            /* Fallback for vertical text in DOMPDF */
            writing-mode: vertical-rl;
            text-orientation: upright;
            letter-spacing: 2px;
        }
        .col-jam {
            width: 25px;
        }
        .col-waktu {
            width: 60px;
        }
        .cell-mapel {
            font-size: 7.5pt;
            text-align: left;
            padding-left: 4px !important;
        }
        
        /* Istirahat & Kegiatan Khusus */
        .row-kegiatan td {
            background-color: #ffffff;
            font-weight: bold;
            text-align: center;
            letter-spacing: 1px;
            font-size: 8pt;
        }
        
        /* Pemisah Hari */
        .day-separator {
            height: 6px;
            background-color: #ff0000; /* Thick red line */
            border: 1px solid #ff0000;
        }
    </style>
</head>
<body>

    @foreach($tingkatList as $index => $tingkat)
        @php
            $kelasTingkat = $kelasByTingkat[$tingkat->id] ?? [];
            if (count($kelasTingkat) === 0) continue;
        @endphp

        <!-- Header -->
        <div class="kop-surat">
            <div class="kop-surat-inner">
                <table style="width: 100%; text-align: center; border: none;">
                    <tr>
                        <td style="width: 15%; border: none;">
                            <!-- Placeholder Logo Kiri -->
                            <div style="width: 60px; height: 80px; border: 1px dashed #ccc; display: inline-block; line-height: 80px; font-size: 8pt; color: #999;">LOGO</div>
                        </td>
                        <td style="width: 70%; border: none;">
                            <h1>PEMERINTAH PROVINSI RIAU</h1>
                            <h1>DINAS PENDIDIKAN</h1>
                            <h2>SEKOLAH MENENGAH ATAS (SMA) NEGERI 1 TAPUNG HULU</h2>
                            
                            <div class="info-grid">
                                <div class="info-row">
                                    <div class="info-cell">Alamat</div>
                                    <div class="info-cell">: Jalan Kampung Lama No. 10 Kasikan</div>
                                    <div class="info-cell">Kode Pos</div>
                                    <div class="info-cell">: 28464</div>
                                </div>
                                <div class="info-row">
                                    <div class="info-cell">Email</div>
                                    <div class="info-cell">: sma.negeri1.tapunghulu@gmail.com</div>
                                    <div class="info-cell">Telp/ HP</div>
                                    <div class="info-cell">: 085271991329</div>
                                </div>
                                <div class="info-row">
                                    <div class="info-cell">NSS</div>
                                    <div class="info-cell">: 301140641001</div>
                                    <div class="info-cell">NPSN</div>
                                    <div class="info-cell">: 10494916</div>
                                </div>
                            </div>
                            
                            <div class="akreditasi">Akreditasi : A</div>
                        </td>
                        <td style="width: 15%; border: none;">
                            <!-- Placeholder Logo Kanan -->
                            <div style="width: 70px; height: 70px; border: 1px dashed #ccc; display: inline-block; border-radius: 50%; line-height: 70px; font-size: 8pt; color: #999;">LOGO</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Tabel -->
        <table class="jadwal-table">
            <thead>
                <tr>
                    <th rowspan="3" class="col-hari">HARI</th>
                    <th colspan="2">ALOKASI WAKTU</th>
                    <th colspan="{{ count($kelasTingkat) }}">JADWAL PELAJARAN</th>
                </tr>
                <tr>
                    <th rowspan="2" class="col-jam">JAM KE</th>
                    <th rowspan="2" class="col-waktu">WAKTU</th>
                    <th colspan="{{ count($kelasTingkat) }}">KELAS {{ $tingkat->nama }}</th>
                </tr>
                <tr>
                    @foreach($kelasTingkat as $kelas)
                        <th>{{ $kelas->nama }}</th>
                    @endforeach
                </tr>
            </thead>
            @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $hari)
                <tbody style="page-break-inside: avoid;">
                    @php
                        $jams = $jamPelajaran->get($hari);
                        if (!$jams || $jams->isEmpty()) continue;
                        $jamsCount = $jams->count();
                    @endphp

                    @foreach($jams as $jIdx => $jam)
                        <tr>
                            @if($jIdx === 0)
                                <td rowspan="{{ $jamsCount }}" class="col-hari">
                                    @php
                                        // DOMPDF support for vertical text is limited, using br tags as fallback
                                        $chars = str_split(strtoupper($hari));
                                    @endphp
                                    @foreach($chars as $char)
                                        {{ $char }}<br>
                                    @endforeach
                                </td>
                            @endif

                            <td class="col-jam">{{ $jam->jam_ke }}</td>
                            <td class="col-waktu">{{ substr($jam->jam_mulai, 0, 5) }}-{{ substr($jam->jam_selesai, 0, 5) }}</td>

                            @if($jam->is_istirahat || $jam->nama_kegiatan)
                                <td colspan="{{ count($kelasTingkat) }}" style="text-align: center; font-weight: bold; background-color: #fff; letter-spacing: 1px;">
                                    {{ $jam->nama_kegiatan ?? 'Istirahat' }}
                                </td>
                            @else
                                @foreach($kelasTingkat as $kelas)
                                    @php
                                        // Find jadwal for this cell
                                        $jdwList = $jadwalGrouped[$tingkat->id][$hari][$jam->id][$kelas->id] ?? [];
                                    @endphp
                                    
                                    <td class="cell-mapel">
                                        @if(count($jdwList) > 0)
                                            @foreach($jdwList as $jdw)
                                                @php
                                                    $guruId = $jdw->guru_id;
                                                    $mapelNama = $jdw->mapel->nama;
                                                @endphp
                                                <div>{{ $guruId }} {{ $mapelNama }}</div>
                                            @endforeach
                                        @else
                                            &nbsp;
                                        @endif
                                    </td>
                                @endforeach
                            @endif
                        </tr>
                    @endforeach
                    
                    <!-- Red Separator Row -->
                    <tr>
                        <td colspan="{{ 3 + count($kelasTingkat) }}" class="day-separator"></td>
                    </tr>
                </tbody>
            @endforeach
        </table>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>
</html>
