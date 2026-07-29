<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Mapel;
use Illuminate\Support\Facades\DB;

// Cek parallel mapel
$parallel = Mapel::where('is_parallel', 1)->get(['id','nama','kelompok_paralel','jam_per_minggu','jam_per_hari']);
echo "=== MAPEL IS_PARALLEL ===\n";
foreach ($parallel as $m) {
    echo "  ID:{$m->id} | {$m->nama} | kelompok:{$m->kelompok_paralel} | jam/minggu:{$m->jam_per_minggu} | jam/hari:{$m->jam_per_hari}\n";
}

// Cek berapa slot agama yang disimpan per kelas (harusnya 2 row per slot jika 2 agama)
echo "\n=== SAMPLE JADWAL KELAS X-1 (agama) ===\n";
$rows = DB::select("
    SELECT j.hari, jp.jam_ke, m.nama mapel, g.nama_guru guru
    FROM jadwal j
    JOIN jam_pelajaran jp ON jp.id = j.jam_pelajaran_id
    JOIN mapel m ON m.id = j.mapel_id
    JOIN guru g ON g.id = j.guru_id
    JOIN kelas k ON k.id = j.kelas_id
    WHERE k.nama = 'X-1' AND m.is_parallel = 1
    ORDER BY jp.jam_ke
");
foreach ($rows as $r) {
    echo "  {$r->hari} jam {$r->jam_ke}: {$r->mapel} | {$r->guru}\n";
}

// Cek apakah GA evaluasi agama sebagai clash
echo "\n=== DIAGNOSA: slot yang sama di X-1 ===\n";
$dups = DB::select("
    SELECT j.hari, jp.jam_ke, COUNT(*) cnt, GROUP_CONCAT(m.nama SEPARATOR ' | ') mapels
    FROM jadwal j
    JOIN jam_pelajaran jp ON jp.id = j.jam_pelajaran_id
    JOIN mapel m ON m.id = j.mapel_id
    JOIN kelas k ON k.id = j.kelas_id
    WHERE k.nama = 'X-1'
    GROUP BY j.hari, jp.jam_ke
    HAVING cnt > 1
");
foreach ($dups as $d) {
    echo "  {$d->hari} jam {$d->jam_ke} | {$d->cnt}x | {$d->mapels}\n";
}
if (empty($dups)) echo "  Tidak ada slot dobel.\n";
