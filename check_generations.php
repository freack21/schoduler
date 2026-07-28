<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ScheduleGeneration;

$gens = ScheduleGeneration::orderBy('id', 'desc')->take(5)->get();
foreach ($gens as $g) {
    echo "ID: {$g->id} | Status: {$g->status} | Gen: {$g->generation}/{$g->max_generations} | Hard: {$g->violations} | Dist: {$g->dist_violations} | Message: {$g->message}\n";
}
