<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /** Intentos fallidos permitidos antes de bloquear temporalmente el acceso. */
    private const MAX_INTENTOS = 5;

    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Ingrese su correo electrónico institucional.',
            'email.email'       => 'Ingrese un correo electrónico válido.',
            'password.required' => 'Ingrese su contraseña.',
        ]);

        $llave = Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($llave, self::MAX_INTENTOS)) {
            $segundos = RateLimiter::availableIn($llave);
            throw ValidationException::withMessages([
                'email' => "Demasiados intentos fallidos. Intente nuevamente en {$segundos} segundos.",
            ]);
        }

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($llave, 60);
            throw ValidationException::withMessages([
                'email' => 'El correo electrónico o la contraseña no son correctos.',
            ]);
        }

        RateLimiter::clear($llave);

        if (! Auth::user()->isActivo()) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Su cuenta se encuentra inactiva o suspendida. Comuníquese con el administrador del sistema.',
            ]);
        }

        $request->session()->regenerate();

        Auth::user()->update([
            'ultimo_acceso'    => now(),
            'ip_ultimo_acceso' => $request->ip(),
        ]);

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Bienvenido(a), ' . Auth::user()->name . '.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Su sesión se cerró correctamente.');
    }
}
