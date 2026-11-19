<?php

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Foundation\AccessGrant;
use App\Models\Foundation\AccessGroup;
use App\Models\Foundation\Delegation;
use App\Models\Foundation\GroupMembership;
use App\Models\Foundation\Module;
use App\Models\Foundation\User;
use App\Services\Access\AccessManager;
use App\Services\Access\ModuleAccess;
use App\Support\Foundation\TableQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AccessController extends Controller
{
    public function index(Request $request, ModuleAccess $access)
    {
        Gate::authorize('manage-access');
        $actor = $request->user();
        $admin = $access->administrator($actor);
        $users = $access->visibleUsers($actor)->orderBy('USName')->get();
        $targets = $admin ? $users : $users->filter(fn ($user) => $user->getKey() !== $actor->getKey() && ! $access->administrator($user) && ! $access->pic($user));
        $leafModules = Module::query()->whereNotNull('MOParent_MONo')->where('MOActive', true)->whereNotIn('MOCode', ['settings.access', 'people.recovery', 'people.history'])->with('parent')->get();
        if (! $admin) {
            $leafModules = $leafModules->filter(fn ($module) => $module->MODelegable && $actor->delegations()->where('DL_MONo', $module->MOParent_MONo)->where('DLActive', true)->exists());
        }
        $groups = AccessGroup::query()->where('AG_CONo', $actor->US_CONo)->where('AGActive', true)->with('items.module')->get();
        if (! $admin) {
            $groups = $groups->filter(fn ($group) => $group->items->where('GIActive', true)->every(fn ($item) => $leafModules->contains('MONo', $item->GI_MONo)
                && $access->can($actor, $item->module, $item->GILevel)));
        }
        $grants = AccessGrant::query()->whereHas('user', fn ($q) => $q->where('US_CONo', $actor->US_CONo))->where('AXActive', true)->with(['user', 'module', 'grantor', 'membership.group']);
        if (! $admin) {
            $grants->where('AXGrantor_USNo', $actor->getKey())->whereHas('user', fn ($q) => $q->where('US_TMNo', $actor->US_TMNo));
        }

        $delegations = Delegation::query()->whereHas('user', fn ($q) => $q->where('US_CONo', $actor->US_CONo))
            ->when(! $admin, fn ($q) => $q->where('DL_USNo', $actor->getKey()))
            ->with(['user', 'module']);
        $memberships = GroupMembership::query()->where('GMActive', true)
            ->whereHas('user', fn ($q) => $q->where('US_CONo', $actor->US_CONo))
            ->when(! $admin, fn ($q) => $q->where('GMGrantor_USNo', $actor->USNo)
                ->whereHas('user', fn ($q) => $q->where('US_TMNo', $actor->US_TMNo)))
            ->with(['user', 'group', 'grantor']);

        $delegationTable = new TableQuery($request, 'authority');
        $grantTable = new TableQuery($request, 'grants');
        $membershipTable = new TableQuery($request, 'assignments');
        $status = $delegationTable->value('status', ['active', 'revoked']);
        $level = $grantTable->value('level', ['read', 'edit']);
        $delegations->when($status !== '', fn ($q) => $q->where('DLActive', $status === 'active'))->orderByDesc('DLID');
        $grants->when($level !== '', fn ($q) => $q->where('AXLevel', $level))->orderByDesc('AXID');
        $memberships->orderBy('GMID', $membershipTable->value('sort', ['asc', 'desc'], 'desc'));

        return view('foundation.access', [
            'title' => 'Groups & module access', 'description' => 'Assign bounded permissions, with a traceable source for every grant.',
            'admin' => $admin, 'targets' => $targets, 'leafModules' => $leafModules, 'groups' => $groups,
            'roots' => Module::query()->whereNull('MOParent_MONo')->where('MOActive', true)->where('MODelegable', true)->get(),
            'pics' => $admin ? $users->filter(fn ($user) => $access->pic($user) && $access->eligible($user)) : collect(),
            'delegations' => $delegationTable->paginate($delegationTable->search($delegations, [], ['user' => ['USName', 'USWorkerID'], 'module' => ['MOName']])),
            'grants' => $grantTable->paginate($grantTable->search($grants, [], ['user' => ['USName', 'USWorkerID'], 'module' => ['MOName'], 'grantor' => ['USName']])),
            'memberships' => $membershipTable->paginate($membershipTable->search($memberships, [], ['user' => ['USName', 'USWorkerID'], 'group' => ['AGName'], 'grantor' => ['USName']])),
        ]);
    }

    public function group(Request $request, AccessManager $manager)
    {
        Gate::authorize('manage-access');
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('MSAccessGroup', 'AGName')->where('AG_CONo', $request->user()->US_CONo)], 'entries' => ['required', 'array', 'min:1', 'max:50'],
            'entries.*.module' => ['required', 'string', 'size:10', 'distinct'], 'entries.*.level' => ['required', 'in:read,edit']]);
        $manager->group($request->user(), $data['name'], $data['entries']);

        return back()->with('status', 'Permission group created.');
    }

    public function delegation(Request $request, AccessManager $manager)
    {
        Gate::authorize('manage-access');
        $data = $request->validate(['user' => ['required', 'string', 'size:10'], 'module' => ['required', 'string', 'size:10'], 'level' => ['required', 'in:read,edit'], 'active' => ['required', 'boolean']]);
        $manager->delegation($request->user(), User::query()->findOrFail($data['user']), Module::query()->findOrFail($data['module']), $data['level'], (bool) $data['active']);

        return back()->with('status', $data['active'] ? 'PIC authority updated.' : 'PIC authority revoked. Grants derived from it are disabled.');
    }

    public function direct(Request $request, AccessManager $manager)
    {
        Gate::authorize('manage-access');
        $data = $request->validate(['users' => ['required', 'array', 'min:1', 'max:100'], 'users.*' => ['required', 'string', 'size:10', 'distinct'], 'module' => ['required', 'string', 'size:10'], 'level' => ['required', 'in:read,edit']]);
        $manager->direct($request->user(), $data['users'], Module::query()->findOrFail($data['module']), $data['level']);

        return back()->with('status', 'Individual access assigned.');
    }

    public function assignGroup(Request $request, AccessManager $manager)
    {
        Gate::authorize('manage-access');
        $data = $request->validate(['users' => ['required', 'array', 'min:1', 'max:100'], 'users.*' => ['required', 'string', 'size:10', 'distinct'], 'group' => ['required', 'string', 'size:10']]);
        $manager->assignGroup($request->user(), $data['users'], AccessGroup::query()->findOrFail($data['group']));

        return back()->with('status', 'Group access assigned.');
    }

    public function revoke(Request $request, AccessGrant $grant, AccessManager $manager)
    {
        Gate::authorize('manage-access');
        $manager->revoke($request->user(), $grant);

        return back()->with('status', 'Grant revoked. Other independent grants remain in effect.');
    }

    public function deactivateGroup(Request $request, AccessGroup $group, AccessManager $manager)
    {
        Gate::authorize('manage-access');
        $manager->deactivateGroup($request->user(), $group);

        return back()->with('status', 'Group disabled. Its grants no longer authorize access.');
    }

    public function revokeMembership(Request $request, GroupMembership $membership, AccessManager $manager)
    {
        Gate::authorize('manage-access');
        $manager->revokeMembership($request->user(), $membership);

        return back()->with('status', 'Group assignment removed. Other independent grants remain.');
    }
}
