<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('vulnfeed:refresh')->dailyAt('03:17')->withoutOverlapping();
Schedule::command('sites:ping')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('sites:whois')->dailyAt('04:05')->withoutOverlapping();
Schedule::command('credits:reset')->hourly()->withoutOverlapping();
