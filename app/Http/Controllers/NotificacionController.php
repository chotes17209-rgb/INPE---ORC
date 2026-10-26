<?php

namespace App\Http\Controllers;

use App\Models\NotificacionSistema;
use Illuminate\Http\RedirectResponse;

class NotificacionController extends Controller
{
    /** Marca la notificación como leída y redirige a la acción asociada. */
    public function abrir(NotificacionSistema $notificacion): RedirectResponse
    {
        abort_unless($notificacion->user_id === auth()->id(), 403);

        if (! $notificacion->leida) {
            $notificacion->marcarLeida();
        }

        return $notificacion->url_accion
            ? redirect()->to($notificacion->url_accion)
            : back();
    }

    public function marcarTodas(): RedirectResponse
    {
        NotificacionSistema::where('user_id', auth()->id())
            ->where('leida', false)
            ->update(['leida' => true, 'leida_en' => now()]);

        return back()->with('success', 'Todas las notificaciones fueron marcadas como leídas.');
    }
}
