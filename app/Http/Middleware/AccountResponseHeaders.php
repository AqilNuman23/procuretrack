<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AccountResponseHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Referrer-Policy', $request->routeIs('password.*', 'recovery.verify*', 'invitation.*', 'activation.*') ? 'no-referrer' : 'same-origin');

        return $response;
    }
}
