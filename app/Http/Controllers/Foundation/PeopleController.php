<?php

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Foundation\Team;
use App\Models\Foundation\User;
use App\Services\Access\ModuleAccess;
use App\Services\PeopleAdministration;
use App\Support\Foundation\TableQuery;
use Illuminate\Http\Request;

class PeopleController extends Controller
{
    public function index(Request $request, ModuleAccess $access)
    {
        abort_unless($access->administrator($request->user()), 403);
        $table = new TableQuery($request);
        $status = $table->value('status', ['active', 'inactive']);
        $query = Team::query()->where('TM_CONo', $request->user()->US_CONo)
            ->when($status !== '', fn ($q) => $q->where('TMActive', $status === 'active'))
            ->with(['picAssignment.user'])->withCount('members')->orderBy('TMName')->orderBy('TMNo');
        $teams = $table->paginate($table->search($query, ['TMName', 'TMCode'], ['picAssignment.user' => ['USName', 'USWorkerID']]));

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
