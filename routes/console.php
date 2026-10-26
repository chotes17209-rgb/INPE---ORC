<?php

use Illuminate\Support\Facades\Schedule;

// Actualiza estados vencidos y genera alertas diarias (requiere "php artisan schedule:work" o una tarea programada del sistema).
Schedule::command('operaciones:verificar-vencimientos')->dailyAt('07:00');
