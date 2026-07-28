<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach ([51, 52, 53, 54] as $id) {
    $k = App\Models\Kelas::find($id);
    echo "ID: {$id} -> Name: " . ($k->nama ?? 'NULL') . "\n";
}
