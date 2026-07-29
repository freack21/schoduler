<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use App\Models\ScheduleGeneration;

echo "=== MULAILAH MENCOBA GENERATE JADWAL SAMPAI 0 BENTROK ===\n";

$attempt = 1;
while (true) {
    echo "\n--------------------------------------------------\n";
    echo "Percobaan ke-{$attempt} dimulai...\n";
    
    // 1. Reset data jadwal lama
    echo "Resetting data jadwal...\n";
    Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    $deletedJadwal = \App\Models\Jadwal::query()->delete();
    $deletedHistory = \App\Models\ScheduleGeneration::query()->delete();
    Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    echo "✅ Menghapus $deletedJadwal record dari tabel jadwal.\n";
    echo "✅ Menghapus $deletedHistory record dari tabel riwayat generate.\n";
    
    // 2. Restart queue worker
    echo "Restarting queue workers...\n";
    Artisan::call('queue:restart');
    echo Artisan::output();
    sleep(2);
    
    // 3. Jalankan generator jadwal
    echo "Running jadwal:generate...\n";
    
    // We will run this via shell exec to get live output or let it block
    $descriptorspec = [
        0 => ["pipe", "r"], // stdin
        1 => ["pipe", "w"], // stdout
        2 => ["pipe", "w"]  // stderr
    ];
    
    $process = proc_open('php artisan jadwal:generate --timeout=2400', $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        // Read output line by line
        while ($line = fgets($pipes[1])) {
            echo $line;
        }
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $return_value = proc_close($process);
    } else {
        $return_value = -1;
    }
    
    // 4. Periksa status generasi terbaru dari database
    $latestGen = ScheduleGeneration::orderBy('id', 'desc')->first();
    if ($latestGen) {
        $violations = $latestGen->violations;
        $status = $latestGen->status;
        $message = $latestGen->message;
        
        echo "\nHasil Percobaan ke-{$attempt}:\n";
        echo "Status: {$status}\n";
        echo "Bentrok Hard: {$violations}\n";
        echo "Pesan: {$message}\n";
        
        if ($status === 'done' && $violations === 0) {
            echo "\n🎉 SELESAI! Jadwal berhasil digenerate dengan 0 bentrok pada Percobaan ke-{$attempt}!\n";
            break;
        }
    } else {
        echo "Tidak ada record ScheduleGeneration yang ditemukan.\n";
    }
    
    echo "⚠️ Percobaan ke-{$attempt} selesai dengan bentrok. Mencoba kembali...\n";
    $attempt++;
    sleep(3);
}
