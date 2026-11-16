<?php

namespace App\Http\Middleware;

use App\Services\Access\ModuleAccess;
use Closure;
use Illuminate\Http\Request;

class ModulePermission
{
    public function handle(Request $request, Closure $next, string $code, string $level = 'read')
    {
        abort_unless(app(ModuleAccess::class)->can($request->user(), $code, $level), 403);

        return $next($request);
    }
}
