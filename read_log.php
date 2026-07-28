<?php
$logPath = '/var/www/schoduler/storage/logs/laravel.log';
if (file_exists($logPath)) {
    $content = file_get_contents($logPath);
    echo substr($content, -2000) . "\n";
} else {
    echo "LOG FILE NOT FOUND!\n";
}
