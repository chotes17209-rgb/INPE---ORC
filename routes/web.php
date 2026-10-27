<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AI\AsistenteIAController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Administrativo\AlimentoController;
use App\Http\Controllers\Administrativo\OperacionReciprocaController;
use App\Http\Controllers\Administrativo\ParqueAutomotorController;
use App\Http\Controllers\Administrativo\ReporteController;
use App\Http\Controllers\Administrativo\VehiculoMantenimientoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\Servicios\ConsultaRucController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas públicas
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => redirect()->route('login'));
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post')->middleware('throttle:20,1');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Rutas protegidas
|--------------------------------------------------------------------------
| Permisos (definidos en AppServiceProvider):
|   - Cualquier usuario autenticado puede consultar.
|   - can:registrar  → crear y editar registros.
|   - can:aprobar    → registrar pagos, completar, cambiar estados y eliminar.
|   - can:administrar→ usuarios y roles.
|
| Las rutas de escritura se registran antes que las de consulta para que
| "…/create" no sea interpretado como el identificador de un registro.
*/
Route::middleware(['auth', 'active.user'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* ── Servicios auxiliares ── */
    Route::get('/servicios/ruc/{ruc}', ConsultaRucController::class)->name('servicios.ruc')->middleware('throttle:30,1');
    Route::get('/servicios/ruc-buscar', [ConsultaRucController::class, 'buscar'])->name('servicios.ruc-buscar')->middleware('throttle:60,1');
    Route::get('/notificaciones/{notificacion}', [NotificacionController::class, 'abrir'])->name('notificaciones.abrir');
    Route::post('/notificaciones/marcar-todas', [NotificacionController::class, 'marcarTodas'])->name('notificaciones.marcar-todas');

    /* ── Administración ── */
    Route::middleware('can:administrar')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('usuarios', UserController::class);
        Route::patch('usuarios/{usuario}/toggle-estado', [UserController::class, 'toggleEstado'])->name('usuarios.toggle-estado');
        Route::get('usuarios/{usuario}/permisos', [UserController::class, 'permisos'])->name('usuarios.permisos');
        Route::post('usuarios/{usuario}/permisos', [UserController::class, 'actualizarPermisos'])->name('usuarios.permisos.update');
        Route::resource('roles', RoleController::class)->except('show');
    });

    /* ── Parque automotor ── */
    Route::prefix('parque-automotor')->name('parque.')->group(function () {
        Route::middleware('can:registrar')->group(function () {
            Route::resource('vehiculos', ParqueAutomotorController::class)->only(['create', 'store', 'edit', 'update']);
            Route::resource('mantenimientos', VehiculoMantenimientoController::class)->only(['create', 'store', 'edit', 'update']);
        });

        Route::middleware('can:aprobar')->group(function () {
            Route::delete('vehiculos/{vehiculo}', [ParqueAutomotorController::class, 'destroy'])->name('vehiculos.destroy');
            Route::patch('vehiculos/{vehiculo}/cambiar-estado', [ParqueAutomotorController::class, 'cambiarEstado'])->name('vehiculos.cambiar-estado');
            Route::delete('mantenimientos/{mantenimiento}', [VehiculoMantenimientoController::class, 'destroy'])->name('mantenimientos.destroy');
            Route::get('mantenimientos/{mantenimiento}/completar', [VehiculoMantenimientoController::class, 'completar'])->name('mantenimientos.completar');
            Route::post('mantenimientos/{mantenimiento}/completar', [VehiculoMantenimientoController::class, 'marcarCompleto'])->name('mantenimientos.marcar-completo');
        });

        Route::get('reporte-pdf', [ParqueAutomotorController::class, 'reportePDF'])->name('reporte-pdf');
        Route::get('reporte-excel', [ParqueAutomotorController::class, 'reporteExcel'])->name('reporte-excel');
        Route::get('mantenimientos/reporte-pdf', [VehiculoMantenimientoController::class, 'reportePDF'])->name('mantenimientos.reporte-pdf');
        Route::get('mantenimientos/reporte-excel', [VehiculoMantenimientoController::class, 'reporteExcel'])->name('mantenimientos.reporte-excel');

        Route::resource('vehiculos', ParqueAutomotorController::class)->only(['index', 'show']);
        Route::get('vehiculos/{vehiculo}/historial', [ParqueAutomotorController::class, 'historial'])->name('vehiculos.historial');
        Route::resource('mantenimientos', VehiculoMantenimientoController::class)->only(['index', 'show']);
    });

    /* ── Suministro de alimentos ── */
    Route::prefix('alimentos')->name('alimentos.')->group(function () {
        Route::middleware('can:registrar')->group(function () {
            Route::resource('registros', AlimentoController::class)->only(['create', 'store', 'edit', 'update'])
                ->parameters(['registros' => 'registro']);
        });

        Route::middleware('can:aprobar')->group(function () {
            Route::delete('registros/{registro}', [AlimentoController::class, 'destroy'])->name('registros.destroy');
            Route::patch('registros/{registro}/aprobar', [AlimentoController::class, 'aprobar'])->name('registros.aprobar');
        });

        Route::get('resumen-mensual', [AlimentoController::class, 'resumenMensual'])->name('resumen-mensual');
        Route::get('reporte-pdf', [AlimentoController::class, 'reportePDF'])->name('reporte-pdf');
        Route::get('reporte-excel', [AlimentoController::class, 'reporteExcel'])->name('reporte-excel');
        Route::get('registros/{registro}/comprobante-pdf', [AlimentoController::class, 'comprobantePDF'])->name('registros.comprobante-pdf');
        Route::resource('registros', AlimentoController::class)->only(['index', 'show'])
            ->parameters(['registros' => 'registro']);
    });

    /* ── Operaciones recíprocas ── */
    Route::prefix('operaciones-reciprocas')->name('operaciones.')->group(function () {
        Route::middleware('can:registrar')->group(function () {
            Route::resource('registros', OperacionReciprocaController::class)->only(['create', 'store', 'edit', 'update'])
                ->parameters(['registros' => 'operacion']);
        });

        Route::middleware('can:aprobar')->group(function () {
            Route::delete('registros/{operacion}', [OperacionReciprocaController::class, 'destroy'])->name('registros.destroy');
            Route::patch('registros/{operacion}/aprobar', [OperacionReciprocaController::class, 'aprobar'])->name('registros.aprobar');
        });

        Route::get('resumen-mensual', [OperacionReciprocaController::class, 'resumenMensual'])->name('resumen-mensual');
        Route::get('reporte-pdf', [OperacionReciprocaController::class, 'reportePDF'])->name('reporte-pdf');
        Route::get('reporte-excel', [OperacionReciprocaController::class, 'reporteExcel'])->name('reporte-excel');
        Route::get('registros/{operacion}/comprobante-pdf', [OperacionReciprocaController::class, 'comprobantePDF'])->name('registros.comprobante-pdf');
        Route::resource('registros', OperacionReciprocaController::class)->only(['index', 'show'])
            ->parameters(['registros' => 'operacion']);
    });

    /* ── Reportes ── */
    Route::prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');
        Route::get('/general-pdf', [ReporteController::class, 'generalPDF'])->name('general-pdf');
        Route::get('/general-excel', [ReporteController::class, 'generalExcel'])->name('general-excel');
        Route::get('/balance-presupuestal', [ReporteController::class, 'balancePresupuestal'])->name('balance-presupuestal');
    });

    /* ── Asistente de inteligencia artificial ── */
    Route::prefix('asistente-ia')->name('ia.')->middleware('throttle:30,1')->group(function () {
        Route::get('/', [AsistenteIAController::class, 'index'])->name('index');
        Route::post('/consultar', [AsistenteIAController::class, 'consultar'])->name('consultar');
        Route::post('/analizar-vehiculo', [AsistenteIAController::class, 'analizarVehiculo'])->name('analizar-vehiculo');
        Route::post('/predecir-mantenimiento', [AsistenteIAController::class, 'predecirMantenimiento'])->name('predecir-mantenimiento');
        Route::post('/alertas-operaciones', [AsistenteIAController::class, 'alertasOperaciones'])->name('alertas-operaciones');
        Route::get('/resumen-ejecutivo-pdf', [AsistenteIAController::class, 'resumenEjecutivoPDF'])->name('resumen-ejecutivo-pdf');
    });
});
