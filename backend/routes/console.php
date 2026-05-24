<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\ServicioReporte;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tarea programada para verificar vencimientos diariamente
Schedule::call(function () {
    ServicioReporte::where('estado', 'ACTIVO')
        ->where('fecha_limite', '<', now())
        ->update(['estado' => 'BLOQUEADO_VENCIDO']);
})->daily();