<?php

use App\Jobs\GenerateScheduleJob;
use App\Models\ScheduleGeneration;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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

    Artisan::call('queue:clear');

    $genState = ScheduleGeneration::create([
        'status' => 'starting',
        'generation' => 0,
        'fitness' => 0,
        'violations' => 0,
        'dist_violations' => 0,
        'max_generations' => 300,
        'started_at' => now(),
    ]);

    GenerateScheduleJob::dispatch($genState->id);

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
        $genState->refresh();
        
        $status = $genState->status;
        $generation = $genState->generation;
        $maxGenerations = $genState->max_generations;
        $fitness = $genState->fitness;
        $hardViolations = $genState->violations;
        $distViolations = $genState->dist_violations;
        $elapsed = time() - $startedAt;

        $percent = $maxGenerations > 0 ? min(100, (int) floor(($generation / $maxGenerations) * 100)) : 0;
        $barWidth = 32;
        $filled = (int) floor(($percent / 100) * $barWidth);
        $bar = str_repeat('█', $filled) . str_repeat('░', $barWidth - $filled);

        $line = sprintf(
            ' %s %3d%% | gen %d/%d | fitness %.6f | hard %s | dist %s | %s | %ss',
            $bar,
            $percent,
            $generation,
            $maxGenerations,
            $fitness,
            $hardViolations,
            $distViolations,
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
            $genState->update([
                'status' => 'error',
                'message' => 'Queue worker berhenti tidak wajar sebelum jadwal selesai.',
                'completed_at' => now(),
            ]);
            break;
        }

        if ($elapsed > $timeout) {
            $process->stop(3);
            $genState->update([
                'status' => 'error',
                'message' => "Generate jadwal timeout setelah {$timeout} detik.",
                'completed_at' => now(),
            ]);
            break;
        }

        sleep($poll);
    }

    if ($process->isRunning()) {
        $process->wait();
    }

    $this->newLine(2);

    $genState->refresh();
    $status = $genState->status;
    $message = $genState->message ?? 'Tidak ada pesan dari generator.';
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
