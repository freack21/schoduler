<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$latest = App\Models\ScheduleGeneration::latest()->first();
if ($latest) {
    echo "ID: {$latest->id} | Status: {$latest->status} | Gen: {$latest->current_generation} | Created: {$latest->created_at}\n";
} else {
    echo "NO GENERATION RECORD FOUND!\n";
}
