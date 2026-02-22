<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// FASE 3: Job Backend (Sistem Otomatis) akan melepaskan kursi yang batas waktunya habis (15 menit)
Schedule::command('tickets:release-expired')->everyMinute();