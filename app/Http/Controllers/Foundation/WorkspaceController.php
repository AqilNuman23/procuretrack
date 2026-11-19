<?php

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Foundation\Module;
use App\Models\Foundation\User;
use App\Notifications\AccountMail;
use App\Rules\ApplicationPassword;
use App\Services\Access\ModuleAccess;
use App\Services\AccountMailer;
use App\Support\Foundation\FoundationWriter;
use App\Support\Foundation\TableQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class WorkspaceController extends Controller
{
    public function index(Request $request, ModuleAccess $access)
    {
        $modules = Module::query()->whereNull('MOParent_MONo')->where('MOActive', true)->get()->filter(fn ($module) => $access->can($request->user(), $module));

        return view('foundation.hub', ['title' => 'Your workspace, connected.', 'description' => 'Choose an area to get started.', 'modules' => $modules]);
    }

    public function module(Request $request, Module $module, ModuleAccess $access)
    {
        Gate::authorize('view', $module);
        if ($module->MOCode === 'settings.components') {
            return redirect()->route('components.index');
        }
        if ($module->MOCode === 'settings.departments') {
            return redirect()->route('departments.index');
        }
        if ($module->MOCode === 'people.invitations') {
            return redirect()->route('invitations.index');
        }
        if ($module->MOCode === 'people.mailboxes') {
            return redirect()->route('mailboxes.index');
        }
        if ($module->MOCode === 'people.teams') {
            return redirect()->route('teams.index');
        }
        if ($module->MOCode === 'settings.access') {
            return redirect()->route('access.index');
        }
        if ($module->MOCode === 'people.recovery') {
            return redirect()->route('recovery.index');
        }
        if ($module->MOCode === 'people.history') {
            return redirect()->route('activity.index');
        }
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:150'], 'status' => ['nullable', 'in:active,deactivated,pending_activation']]);
        $users = $module->MOCode === 'people.users' ? (new TableQuery($request))->paginate($access->visibleUsers($request->user())->with('team')
            ->when(isset($filters['search']), fn ($q) => $q->where(fn ($q) => $q->where('USName', 'like', '%'.$filters['search'].'%')->orWhere('USWorkerID', 'like', '%'.$filters['search'].'%')))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('USStatus', $value))
            ->orderBy('USName')->orderBy('USNo')) : null;
        $children = $module->children()->where('MOActive', true)->get()->filter(fn ($child) => $access->can($request->user(), $child));

        return view('foundation.module', ['title' => $module->MOName, 'description' => config('modules')[$module->MOCode]['description'] ?? '', 'currentModule' => $module, 'children' => $children, 'users' => $users]);
    }

    public function company(Request $request, ModuleAccess $access, FoundationWriter $writer)
    {
        $module = Module::query()->where('MOCode', 'settings.company')->firstOrFail();
        Gate::authorize('edit', $module);
        $data = $request->validate(['name' => ['required', 'string', 'max:150']]);
        $writer->update($request->user()->company, ['COName' => $data['name']], $request->user());

        return back()->with('status', 'Company name updated.');
    }

    public function user(User $user)
    {
        Gate::authorize('view', $user);

        return view('foundation.user', ['title' => $user->USName, 'description' => 'Account and team information.', 'person' => $user]);
    }

    public function profile(Request $request)
    {
        return view('foundation.profile', ['title' => 'My account', 'description' => 'Your identity, recovery address, and private password.']);
    }

    public function password(Request $request, FoundationWriter $writer, AccountMailer $mailer)
    {
        $data = $request->validate(['current_password' => ['required', 'string', 'max:128'], 'password' => ['required', 'string', new ApplicationPassword, 'confirmed']]);
        $user = $request->user();
        if (strlen($data['current_password']) > 72 || ! Hash::check($data['current_password'], $user->USPassword)) {
            throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
        }
        $writer->transaction($user, function ($changes) use ($user, $data) {
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->USAuthVersion !== $user->USAuthVersion || ! Hash::check($data['current_password'], $locked->USPassword)) {
                abort(409, 'Account changed. Sign in again.');
            }
            $changes->update($locked, ['USPassword' => $data['password'], 'USAuthVersion' => $locked->USAuthVersion + 1]);
        });
        $mailer->send($user->USEmail, new AccountMail('changed'));
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Password changed. All existing sessions are invalid. Sign in again.');
    }
}
