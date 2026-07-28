<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Mapel;

foreach (App\Models\Kelas::all() as $k) {
    $kuriList = App\Models\Kurikulum::where('tingkat_id', $k->tingkat_id)->get();
    $load = 0;
    foreach ($kuriList as $kuri) {
        if (is_null($kuri->jurusan_id) || $kuri->jurusan_id == $k->jurusan_id) {
            $load += $kuri->mapel->jam_per_minggu;
        }
    }
    echo "Kelas: {$k->nama} -> Beban: {$load} jam/minggu\n";
}
exit;
