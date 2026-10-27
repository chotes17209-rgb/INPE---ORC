<?php

namespace App\Providers;

use App\Models\NotificacionSistema;
use App\Models\User;
use App\Services\AI\OpenAIService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OpenAIService::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // En producción todas las direcciones se generan con HTTPS.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->definirPermisos();

        // Notificaciones del usuario autenticado, disponibles en el layout principal.
        View::composer('layouts.app', function ($view) {
            if (! auth()->check()) {
                return;
            }
            $view->with([
                'totalNoLeidas'        => NotificacionSistema::where('user_id', auth()->id())->where('leida', false)->count(),
                'notificacionesMenu'   => NotificacionSistema::where('user_id', auth()->id())->latest()->take(8)->get(),
            ]);
        });
    }

    /**
     * Matriz de permisos del sistema (control de acceso basado en roles).
     *
     * | Permiso      | super-admin | admin | contador | asistente | visualizador |
     * |--------------|:-----------:|:-----:|:--------:|:---------:|:------------:|
     * | consultar    |      ✔      |   ✔   |    ✔     |     ✔     |      ✔       |
     * | registrar    |      ✔      |   ✔   |    ✔     |     ✔     |              |
     * | aprobar      |      ✔      |   ✔   |    ✔     |           |              |
     * | administrar  |      ✔      |   ✔   |          |           |              |
     *
     * - registrar:   crear y editar registros de los módulos contables.
     * - aprobar:     registrar pagos, completar mantenimientos, cambiar estados y eliminar.
     * - administrar: gestionar usuarios y roles.
     */
    private function definirPermisos(): void
    {
        Gate::before(fn (User $user) => $user->hasRole('super-admin') ? true : null);

        Gate::define('registrar',   fn (User $user) => $user->hasAnyRole(['admin', 'contador', 'asistente']));
        Gate::define('aprobar',     fn (User $user) => $user->hasAnyRole(['admin', 'contador']));
        Gate::define('administrar', fn (User $user) => $user->hasRole('admin'));
    }
}
