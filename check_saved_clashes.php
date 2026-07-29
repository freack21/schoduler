<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Cek duplikat kelas+slot dalam jadwal
$dups = DB::select("
    SELECT k.nama kelas, j.hari, jp.jam_ke, COUNT(*) cnt, GROUP_CONCAT(m.nama ORDER BY m.nama SEPARATOR ' | ') mapels
    FROM jadwal j
    JOIN kelas k ON k.id = j.kelas_id
    JOIN jam_pelajaran jp ON jp.id = j.jam_pelajaran_id
    JOIN mapel m ON m.id = j.mapel_id
    GROUP BY j.kelas_id, j.hari, jp.jam_ke
    HAVING cnt > 1
    ORDER BY kelas, j.hari, jp.jam_ke
");

echo "=== DUPLIKAT SLOT KELAS (clash tersimpan di DB) ===\n";
if (empty($dups)) {
    echo "Tidak ada duplikat! Data bersih.\n";
} else {
    foreach ($dups as $d) {
        echo "  {$d->kelas} | {$d->hari} jam {$d->jam_ke} | {$d->cnt}x | {$d->mapels}\n";
    }
}

// Cek duplikat guru+slot
$guruDups = DB::select("
    SELECT g.nama guru, j.hari, jp.jam_ke, COUNT(*) cnt, GROUP_CONCAT(k.nama ORDER BY k.nama SEPARATOR ' | ') kelas
    FROM jadwal j
    JOIN guru g ON g.id = j.guru_id
    JOIN jam_pelajaran jp ON jp.id = j.jam_pelajaran_id
    JOIN kelas k ON k.id = j.kelas_id
    GROUP BY j.guru_id, j.hari, jp.jam_ke
    HAVING cnt > 1
    ORDER BY guru, j.hari, jp.jam_ke
");

echo "\n=== DUPLIKAT SLOT GURU (guru ngajar 2 kelas barengan) ===\n";
if (empty($guruDups)) {
    echo "Tidak ada duplikat guru!\n";
} else {
    foreach ($guruDups as $d) {
        echo "  {$d->guru} | {$d->hari} jam {$d->jam_ke} | {$d->cnt}x | Kelas: {$d->kelas}\n";
    }
}
