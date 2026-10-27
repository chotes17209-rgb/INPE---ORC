<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    /**
     * Respuesta de una operación exitosa.
     *
     * Los formularios de las ventanas flotantes se envían en segundo plano y
     * esperan JSON: el mensaje queda en sesión para mostrarse al recargar la
     * página. Sin JavaScript se redirige a $destino, como siempre.
     *
     * @param bool $irADestino En la ventana flotante, ir a $destino en lugar de
     *                         recargar la página actual (p. ej., tras eliminar).
     */
    protected function exito(string $mensaje, string $destino, bool $irADestino = false): RedirectResponse|JsonResponse
    {
        if (request()->expectsJson()) {
            session()->flash('success', $mensaje);

            return response()->json([
                'ok'       => true,
                'mensaje'  => $mensaje,
                'redirect' => $irADestino ? $destino : null,
            ]);
        }

        return redirect()->to($destino)->with('success', $mensaje);
    }

    /** Operación no permitida por la situación del registro (pagado, cerrado, etc.). */
    protected function aviso(string $mensaje, ?string $destino = null, string $tipo = 'error'): RedirectResponse|JsonResponse
    {
        if (request()->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $mensaje], 409);
        }

        return ($destino ? redirect()->to($destino) : back())->with($tipo, $mensaje);
    }
}
