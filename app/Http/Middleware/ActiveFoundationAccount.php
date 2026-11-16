<?php

namespace App\Http\Middleware;

use App\Models\Foundation\User;
use App\Services\Access\ModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActiveFoundationAccount
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user instanceof User) {
            $user->refresh();
        }
        if (! $user instanceof User || ! $request->hasSession() || ! app(ModuleAccess::class)->eligible($user)
            || $request->session()->get('auth_version') !== $user->USAuthVersion) {
            Auth::guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session is no longer valid. Sign in again.'], 401);
            }

            return redirect()->route('login')->with('status', 'Your session ended or account access changed. Sign in again.');
        }

        return $next($request);
    }
}
