<?php

namespace App\Services;

use App\Models\Foundation\FoundationLog;
use App\Models\Foundation\User;
use App\Services\Access\ModuleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Impersonation
{
    public function __construct(private ModuleAccess $access) {}

    public function administrator(Request $request): ?User
    {
        $origin = $request->session()->get('impersonation');
        if ($origin) {
            $admin = User::query()->find($origin['administrator'] ?? null);

            return $admin && $admin->USAuthVersion === ($origin['version'] ?? null) && $this->access->eligible($admin) && $this->access->administrator($admin) ? $admin : null;
        }
        $admin = $request->user();

        return $admin instanceof User && $this->access->eligible($admin) && $this->access->administrator($admin) ? $admin : null;
    }

    public function record(User $admin, User $target, string $event): void
    {
        // Internal security history: do not change the target's business data just to record a switch.
        $log = new FoundationLog(['FLAction' => 'updated', 'FLTable' => 'MSUser', 'FLRecordNo' => $target->getKey(),
            'FLBefore' => null, 'FLAfter' => ['_impersonation' => $event, '_impersonator' => $admin->getKey(), '_acting_as' => $target->getKey()]]);
        $log->FLCB = $admin->getKey();
        $log->FLMB = $admin->getKey();
        $log->saveOrFail();
    }

    public function login(Request $request, User $user): void
    {
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        $request->session()->put('auth_version', $user->USAuthVersion);
        $request->session()->forget('url.intended');
        $request->session()->forget(['_old_input', 'errors']);
    }
}
