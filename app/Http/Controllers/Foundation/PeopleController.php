<?php

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Foundation\Team;
use App\Models\Foundation\User;
use App\Services\Access\ModuleAccess;
use App\Services\PeopleAdministration;
use Illuminate\Http\Request;

class PeopleController extends Controller
{
    public function index(Request $request, ModuleAccess $access)
    {
        abort_unless($access->administrator($request->user()), 403);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:150']]);
        $teams = Team::query()->where('TM_CONo', $request->user()->US_CONo)
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where(fn ($q) => $q->where('TMName', 'like', '%'.$value.'%')->orWhere('TMCode', 'like', '%'.$value.'%')))
            ->with(['picAssignment.user'])->withCount('members')->orderBy('TMName')->paginate(15)->withQueryString();

        return view('foundation.teams', ['title' => 'Teams', 'description' => 'Manage teams and assign an active member as PIC.', 'teams' => $teams]);
    }

    public function show(Request $request, Team $team, ModuleAccess $access)
    {
        abort_unless($access->administrator($request->user()) && $team->TM_CONo === $request->user()->US_CONo, 403);

        return view('foundation.team', ['title' => $team->TMName, 'description' => 'Team identity and PIC responsibility.', 'team' => $team,
            'members' => $team->members()->orderBy('USName')->get()->filter(fn ($person) => $access->eligible($person))]);
    }

    public function store(Request $request, PeopleAdministration $people)
    {
        $team = $people->team($request->user(), null, $request->all());

        return redirect()->route('teams.show', $team)->with('status', 'Team created.');
    }

    public function update(Request $request, Team $team, PeopleAdministration $people)
    {
        $people->team($request->user(), $team, $request->all());

        return back()->with('status', 'Team updated.');
    }

    public function pic(Request $request, Team $team, PeopleAdministration $people)
    {
        $data = $request->validate(['pic' => ['required', 'string', 'max:10']]);
        $people->assignPic($request->user(), $team, User::query()->findOrFail($data['pic']));

        return back()->with('status', 'PIC assigned. Previous PIC delegations have been revoked where applicable.');
    }

    public function user(Request $request, User $user, PeopleAdministration $people)
    {
        $people->updateUser($request->user(), $user, $request->all());

        return redirect()->route('users.show', $user)->with('status', 'Account updated. Team or status changes end existing sessions. Complete mailbox offboarding separately with the provider.');
    }
}
