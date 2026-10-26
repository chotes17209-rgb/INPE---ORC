<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->estado !== 'activo') {
            Auth::logout();
            $request->session()->invalidate();
            return redirect()->route('login')
                ->with('error', 'Su cuenta ha sido desactivada. Contacte al administrador.');
        }
        return $next($request);
    }
}
