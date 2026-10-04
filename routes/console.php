<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('missoes:gerar-recorrentes')->daily();

// Todo dia, perto da meia-noite: o do último dia do mês é o que fica como fotografia do mês fechado.
Schedule::command('finance:snapshot')->dailyAt('23:55');
