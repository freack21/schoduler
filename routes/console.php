<?php

use App\Jobs\GenerateScheduleJob;
use App\Models\ScheduleGeneration;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('jadwal:generate {--timeout=2400 : Maksimal waktu proses dalam detik} {--poll=1 : Interval refresh progress dalam detik}', function () {
    $timeout = max(60, (int) $this->option('timeout'));
    $poll = max(1, (int) $this->option('poll'));

    $this->newLine();
    $this->components->info('🚀 Generate jadwal dimulai...');
    $this->line('Mode: dispatch job via systemd queue worker + live progress terminal');
    $this->newLine();

    // Bersihkan queue lama
    Artisan::call('queue:clear');

    $genState = ScheduleGeneration::create([
        'status'          => 'starting',
        'generation'      => 0,
        'fitness'         => 0,
        'violations'      => 0,
        'dist_violations' => 0,
        'max_generations' => 1200,
        'started_at'      => now(),
    ]);

    GenerateScheduleJob::dispatch($genState->id);

    // Beri waktu agar job masuk antrian dan worker mengambilnya
    sleep(3);

    $startedAt = time();
    $lastLineLength = 0;
    $lastGen = 0;
    $lastGenChangeAt = time();

    while (true) {
        $genState->refresh();

        $status          = $genState->status;
        $generation      = $genState->generation;
        $maxGenerations  = $genState->max_generations;
        $fitness         = $genState->fitness;
        $hardViolations  = $genState->violations;
        $distViolations  = $genState->dist_violations;
        $elapsed         = time() - $startedAt;

        // Track apakah generasi masih maju
        if ($generation !== $lastGen) {
            $lastGen = $generation;
            $lastGenChangeAt = time();
        }

        $percent = $maxGenerations > 0 ? min(100, (int) floor(($generation / $maxGenerations) * 100)) : 0;
        $barWidth = 32;
        $filled   = (int) floor(($percent / 100) * $barWidth);
        $bar      = str_repeat('█', $filled) . str_repeat('░', $barWidth - $filled);

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

        // Timeout global
        if ($elapsed > $timeout) {
            $genState->update([
                'status'       => 'error',
                'message'      => "Generate jadwal timeout setelah {$timeout} detik.",
                'completed_at' => now(),
            ]);
            break;
        }

        // Deteksi job hilang: tidak ada status 'running' setelah 30 detik
        if ($status === 'starting' && $elapsed > 30) {
            $genState->update([
                'status'       => 'error',
                'message'      => 'Queue worker tidak mengambil job. Pastikan schoduler-queue.service aktif.',
                'completed_at' => now(),
            ]);
            break;
        }

        sleep($poll);
    }

    $this->newLine(2);

    $genState->refresh();
    $status  = $genState->status;
    $message = $genState->message ?? 'Tidak ada pesan dari generator.';

    if ($status === 'done') {
        $this->components->success('✅ Generate jadwal selesai!');
        $this->line($message);
        return self::SUCCESS;
    }

    // Jika timeout tapi violations sudah 0 → masih dianggap sukses
    if ($genState->violations === 0 && $genState->generation > 0) {
        $this->components->success('✅ Generate jadwal selesai (bentrok: 0)!');
        $this->line($message);
        return self::SUCCESS;
    }

    $this->components->error('❌ Generate jadwal gagal / tidak selesai.');
    $this->warn($message);

    return self::FAILURE;
})->purpose('Generate jadwal dari terminal dengan progress interaktif');
