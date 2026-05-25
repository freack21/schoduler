<?php

use App\Jobs\GenerateScheduleJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('jadwal:generate {--timeout=900 : Maksimal waktu proses dalam detik} {--poll=1 : Interval refresh progress dalam detik}', function () {
    $timeout = max(60, (int) $this->option('timeout'));
    $poll = max(1, (int) $this->option('poll'));

    $this->newLine();
    $this->components->info('🚀 Generate jadwal dimulai...');
    $this->line('Mode: dispatch job + auto queue worker + live progress terminal');
    $this->newLine();

    foreach ([
        'ga_status',
        'ga_generation',
        'ga_fitness',
        'ga_violations',
        'ga_dist_violations',
        'ga_max_generations',
        'ga_best_generation',
        'ga_best_hard',
        'ga_best_dist',
        'ga_best_fitness',
        'ga_message',
    ] as $key) {
        Cache::forget($key);
    }

    Cache::put('ga_status', 'starting', $timeout + 120);
    Cache::put('ga_generation', 0, $timeout + 120);
    Cache::put('ga_fitness', 0, $timeout + 120);
    Cache::put('ga_violations', 0, $timeout + 120);
    Cache::put('ga_dist_violations', 0, $timeout + 120);
    Cache::put('ga_message', '', $timeout + 120);

    GenerateScheduleJob::dispatch();

    $process = new Process([
        PHP_BINARY,
        'artisan',
        'queue:work',
        '--stop-when-empty',
        '--timeout=' . $timeout,
        '--tries=1',
    ], base_path(), null, null, $timeout + 30);

    $process->start();

    // Beri waktu sejenak agar job masuk antrian sebelum worker mengecek 'empty'
    usleep(500000);

    $startedAt = time();
    $lastLineLength = 0;

    while (true) {
        $status = Cache::get('ga_status', 'starting');
        $generation = (int) Cache::get('ga_generation', 0);
        $maxGenerations = (int) Cache::get('ga_max_generations', 100);
        $fitness = (float) Cache::get('ga_fitness', 0);
        $hardViolations = (int) Cache::get('ga_violations', 0);
        $distViolations = (int) Cache::get('ga_dist_violations', 0);
        $elapsed = time() - $startedAt;

        $percent = $maxGenerations > 0 ? min(100, (int) floor(($generation / $maxGenerations) * 100)) : 0;
        $barWidth = 32;
        $filled = (int) floor(($percent / 100) * $barWidth);
        $bar = str_repeat('█', $filled) . str_repeat('░', $barWidth - $filled);

        $bestHardLabel = is_numeric(Cache::get('ga_best_hard')) ? Cache::get('ga_best_hard') : '-';
        $bestDistLabel = is_numeric(Cache::get('ga_best_dist')) ? Cache::get('ga_best_dist') : '-';

        $line = sprintf(
            ' %s %3d%% | gen %d/%d | fitness %.6f | hard %s (%s best) | dist %s (%s best) | %s | %ss',
            $bar,
            $percent,
            $generation,
            $maxGenerations,
            $fitness,
            $hardViolations,
            $bestHardLabel,
            $distViolations,
            $bestDistLabel,
            strtoupper((string) $status),
            $elapsed
        );

        $padding = str_repeat(' ', max(0, $lastLineLength - strlen($line)));
        $this->output->write("\r{$line}{$padding}");
        $lastLineLength = strlen($line);

        if (in_array($status, ['done', 'error'], true)) {
            break;
        }

        if (!$process->isRunning() && $status !== 'done') {
            Cache::put('ga_status', 'error', 600);
            Cache::put('ga_message', "Queue worker berhenti tidak wajar sebelum jadwal selesai.", 600);
            break;
        }

        if ($elapsed > $timeout) {
            $process->stop(3);
            Cache::put('ga_status', 'error', 600);
            Cache::put('ga_message', "Generate jadwal timeout setelah {$timeout} detik.", 600);
            break;
        }

        sleep($poll);
    }

    if ($process->isRunning()) {
        $process->wait();
    }

    $this->newLine(2);

    $status = Cache::get('ga_status', 'idle');
    $message = Cache::get('ga_message', 'Tidak ada pesan dari generator.');
    $exitCode = $process->getExitCode();

    if ($status === 'done' && $exitCode === 0) {
        $this->components->success('✅ Generate jadwal selesai!');
        $this->line($message);
        return self::SUCCESS;
    }

    $this->components->error('❌ Generate jadwal gagal / tidak selesai.');
    $this->warn($message);

    $errorOutput = trim($process->getErrorOutput());
    $stdOutput = trim($process->getOutput());

    if ($stdOutput !== '') {
        $this->newLine();
        $this->line('<comment>Queue output:</comment>');
        $this->line($stdOutput);
    }

    if ($errorOutput !== '') {
        $this->newLine();
        $this->line('<error>Queue error:</error>');
        $this->line($errorOutput);
    }

    return self::FAILURE;
})->purpose('Generate jadwal dari terminal dengan progress interaktif');
