<?php

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Foundation\User;
use App\Services\Access\ModuleAccess;
use App\Services\Impersonation;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function users(Request $request, Impersonation $impersonation, ModuleAccess $access)
    {
        abort_unless($admin = $impersonation->administrator($request), 403);
        $data = $request->validate(['search' => ['nullable', 'string', 'max:150']]);
        $users = User::query()->where('US_CONo', $admin->US_CONo)->whereKeyNot($admin->getKey())->where('USStatus', 'active')
            ->whereNotNull('USApprovedAt')->whereNotNull('USActivatedAt')->whereNotNull('USPassword')
            ->when(! app()->environment('local'), fn ($q) => $q->where('USLocalDemo', false))
            ->whereHas('mailbox', fn ($q) => $q->where('MXStatus', 'ready')->when(! app()->environment(['local', 'testing']), fn ($q) => $q->where('MXIsMock', false)))
            ->when($data['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->where('USName', 'like', '%'.$search.'%')->orWhere('USWorkerID', 'like', '%'.$search.'%')))
            ->with('team')->orderBy('USName')->limit(50)->get()->filter(fn ($user) => $access->eligible($user))
            ->take(50)->map(fn ($user) => ['id' => $user->getKey(), 'name' => $user->USName, 'worker_id' => $user->USWorkerID, 'team' => $user->team?->TMName])->values();

        return response()->json(['users' => $users]);
    }

    public function start(Request $request, Impersonation $impersonation, ModuleAccess $access)
    {
        abort_unless($admin = $impersonation->administrator($request), 403);
        $data = $request->validate(['user' => ['required', 'string', 'max:10']]);
        $target = User::query()->findOrFail($data['user']);
        abort_unless($target->US_CONo === $admin->US_CONo && $target->getKey() !== $admin->getKey() && $access->eligible($target), 403);
        $impersonation->record($admin, $target, 'started');
        $request->session()->put('impersonation', ['administrator' => $admin->getKey(), 'version' => $admin->USAuthVersion]);
        $impersonation->login($request, $target);

        return redirect()->route('workspace')->with('status', __('You are now using :name’s permissions.', ['name' => $target->USName]));
    }

    public function stop(Request $request, Impersonation $impersonation)
    {
        abort_unless($request->session()->has('impersonation') && ($admin = $impersonation->administrator($request)), 403);
        $impersonation->record($admin, $request->user(), 'stopped');
        $request->session()->forget('impersonation');
        $impersonation->login($request, $admin);

        return redirect()->route('workspace')->with('status', 'Returned to your administrator account.');
    }
}
