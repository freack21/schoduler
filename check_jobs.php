<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$jobs = DB::table('jobs')->get();
echo "=== CURRENT JOBS IN DB ===\n";
foreach ($jobs as $job) {
    echo "ID: {$job->id} | Queue: {$job->queue} | Attempts: {$job->attempts} | Reserved At: " . ($job->reserved_at ? date('Y-m-d H:i:s', $job->reserved_at) : 'NULL') . "\n";
}
if ($jobs->isEmpty()) {
    echo "NO JOBS IN DB!\n";
}
