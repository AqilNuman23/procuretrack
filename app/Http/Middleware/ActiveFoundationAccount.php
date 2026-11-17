<?php

namespace App\Http\Middleware;

use App\Models\Foundation\User;
use App\Services\Access\ModuleAccess;
use App\Services\Impersonation;
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
        $origin = $request->hasSession() && $request->session()->has('impersonation');
        $admin = $origin ? app(Impersonation::class)->administrator($request) : null;
        if ($origin && $admin && $user instanceof User && $admin->US_CONo === $user->US_CONo
            && (! app(ModuleAccess::class)->eligible($user) || $request->session()->get('auth_version') !== $user->USAuthVersion)) {
            app(Impersonation::class)->record($admin, $user, 'ended_account_changed');
            $request->session()->forget('impersonation');
            app(Impersonation::class)->login($request, $admin);

            return $request->expectsJson() ? response()->json(['message' => 'The selected account changed. Administrator access restored.'], 401)
                : redirect()->route('workspace')->with('status', 'The selected account changed. Returned to your administrator account.');
        }
        if (! $user instanceof User || ! $request->hasSession() || ! app(ModuleAccess::class)->eligible($user)
            || ($origin && (! $admin || $admin->US_CONo !== $user->US_CONo))
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
        abort_if($origin && $request->routeIs('profile.password'), 403, 'Return to your administrator account before managing passwords.');

        return $next($request);
    }
}
