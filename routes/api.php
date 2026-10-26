<?php

use Illuminate\Support\Facades\Route;

// API reservada para una futura integración móvil.
Route::get('/health', fn () => response()->json(['estado' => 'ok', 'sistema' => config('inpe.sistema')]));
