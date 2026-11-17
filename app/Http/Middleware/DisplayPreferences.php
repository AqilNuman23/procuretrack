<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisplayPreferences
{
    public function handle(Request $request, Closure $next)
    {
        app()->setLocale(in_array($request->cookie('locale'), ['en', 'ms'], true) ? $request->cookie('locale') : 'en');
        $request->attributes->set('theme', in_array($request->cookie('theme'), ['dark', 'light'], true) ? $request->cookie('theme') : 'dark');

        return $next($request);
    }
}
