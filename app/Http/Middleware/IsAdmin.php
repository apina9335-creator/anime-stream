<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Kalau user Login DAN dia Admin, boleh masuk
        if (auth()->check() && auth()->user()->is_admin) {
            return $next($request);
        }

        // Kalau bukan, tendang ke halaman depan
        return redirect('/');
    }
}