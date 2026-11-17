<?php

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Foundation\User;
use App\Services\Access\ModuleAccess;
use App\Services\Impersonation;
use App\Services\RecoveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('foundation.auth', ['form' => 'login', 'title' => 'Welcome back.', 'description' => 'Sign in with your Worker ID and private password.']);
    }

    public function store(Request $request, RecoveryService $recovery, ModuleAccess $access)
    {
        $data = $request->validate(['login' => ['required', 'string', 'max:50'], 'password' => ['required', 'string', 'max:128']]);
        $identifier = trim($data['login']);
        $user = strtolower($identifier) === 'admin'
            ? User::query()->where('USLoginName', 'admin')->whereHas('company', fn ($q) => $q->where('COCode', config('foundation.company_code')))->first()
            : $recovery->lookup($identifier);
        $matches = strlen($data['password']) <= 72 && Hash::check($data['password'], $user?->USPassword ?: '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if (! $user || ! $matches || ! $access->eligible($user) || (strtolower($identifier) === 'admin' && ! $access->administrator($user))) {
            throw ValidationException::withMessages(['login' => 'The sign-in details are incorrect or this account is not ready for access.']);
        }
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('auth_version', $user->USAuthVersion);

        return redirect()->intended(route('workspace'));
    }

    public function destroy(Request $request)
    {
        if ($request->session()->has('impersonation') && ($admin = app(Impersonation::class)->administrator($request))) {
            app(Impersonation::class)->record($admin, $request->user(), 'logged_out');
        }
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
