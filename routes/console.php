<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('radarleb:smoke', function () {
    $ok = true;
    $this->info('Smoke checks (run on server after deploy):');

    try {
        DB::connection()->getPdo();
        $this->info('  [OK] DB connection');
    } catch (\Throwable $e) {
        $this->error('  [FAIL] DB: '.$e->getMessage());
        $ok = false;
    }

    $logDir = storage_path('logs');
    if (is_writable($logDir)) {
        $this->info('  [OK] storage writable');
    } else {
        $this->error('  [FAIL] storage/logs not writable');
        $ok = false;
    }

    if (config('queue.default') !== 'sync') {
        try {
            \Illuminate\Support\Facades\Queue::connection()->size();
            $this->info('  [OK] queue connection');
        } catch (\Throwable $e) {
            $this->warn('  [WARN] queue: '.$e->getMessage());
        }
    }

    if ($ok) {
        $this->info('Smoke checks passed.');
    } else {
        $this->error('Smoke checks failed.');
        exit(1);
    }
})->purpose('Run post-deploy smoke checks (DB, storage, queue)');


