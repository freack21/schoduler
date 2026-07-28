<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Mapel;
use App\Models\Guru;
use App\Models\Tingkat;
use App\Models\GuruMapel;

$fisikaLanjut = Mapel::where('nama', 'Fisika Lanjutan')->first();
$roindah = Guru::whereHas('user', function($q) {
    $q->where('nama_lengkap', 'like', '%Roindah%');
})->first();

if (!$fisikaLanjut || !$roindah) {
    echo "🚨 ERROR: Fisika Lanjutan atau Guru Roindah tidak ditemukan!\n";
    exit(1);
}

$tingkatXI = Tingkat::where('nama', 'XI')->first();
$tingkatXII = Tingkat::where('nama', 'XII')->first();

echo "=== MEMETAKAN ROINDAH FEBRIZAH SIMBOLON KE FISIKA LANJUTAN ===\n";

if ($tingkatXI) {
    $gm = GuruMapel::firstOrCreate([
        'guru_id' => $roindah->id,
        'mapel_id' => $fisikaLanjut->id,
        'tingkat_id' => $tingkatXI->id,
    ]);
    echo "✅ Berhasil memetakan Roindah ke Fisika Lanjutan Tingkat XI [ID: {$gm->id}]\n";
}

if ($tingkatXII) {
    $gm = GuruMapel::firstOrCreate([
        'guru_id' => $roindah->id,
        'mapel_id' => $fisikaLanjut->id,
        'tingkat_id' => $tingkatXII->id,
    ]);
    echo "✅ Berhasil memetakan Roindah ke Fisika Lanjutan Tingkat XII [ID: {$gm->id}]\n";
}
