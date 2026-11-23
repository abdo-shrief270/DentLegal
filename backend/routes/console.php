<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly roll-up for «إحصائيات الاستخدام» (S6.3), after midnight Riyadh time.
Schedule::command('stats:roll-up')->dailyAt('00:20')->timezone('Asia/Riyadh')->onOneServer()->withoutOverlapping();

// Retention (§9.7, S9.2): nightly, after the stats roll-up.
Schedule::command('retention:purge')->dailyAt('01:10')->timezone('Asia/Riyadh')->onOneServer()->withoutOverlapping();
