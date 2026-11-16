<?php

namespace App\Services;

use App\Models\Foundation\Company;
use App\Models\Foundation\Role;
use App\Models\Foundation\Team;
use App\Models\Foundation\TeamPic;
use App\Models\Foundation\User;
use App\Models\Foundation\UserRole;
use App\Services\Access\ModuleAccess;
use App\Support\Foundation\FoundationChanges;
use App\Support\Foundation\FoundationWriter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PeopleAdministration
{
    public function __construct(private FoundationWriter $writer, private ModuleAccess $access) {}

    // Serialize company administration so two administrators cannot disable each other concurrently.
    private function administrator(User $actor): User
    {
        Company::query()->whereKey($actor->US_CONo)->lockForUpdate()->firstOrFail();
        $current = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
        abort_unless($current->USAuthVersion === $actor->USAuthVersion && $this->access->eligible($current) && $this->access->administrator($current), 403);

        return $current;
    }

    public function team(User $actor, ?Team $team, array $data): Team
    {
        return $this->writer->transaction($actor, function (FoundationChanges $changes) use ($actor, $team, $data) {
            $actor = $this->administrator($actor);
            if ($team) {
                $team = Team::query()->whereKey($team->getKey())->lockForUpdate()->firstOrFail();
                abort_unless($team->TM_CONo === $actor->US_CONo, 403);
            }
            $values = Validator::make($data, [
                'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('MSTeam', 'TMCode')->where('TM_CONo', $actor->US_CONo)->ignore($team?->getKey(), 'TMNo')],
                'name' => ['required', 'string', 'max:150'],
            ])->validate();
            $attributes = ['TMCode' => $values['code'], 'TMName' => $values['name']];

            return $team ? $changes->update($team, $attributes) : $changes->create(Team::class, $attributes + ['TM_CONo' => $actor->US_CONo]);
        });
    }

    public function assignPic(User $actor, Team $team, User $candidate): void
    {
        $this->writer->transaction($actor, function (FoundationChanges $changes) use ($actor, $team, $candidate) {
            $actor = $this->administrator($actor);
            $team = Team::query()->whereKey($team->getKey())->lockForUpdate()->firstOrFail();
            $candidate = User::query()->whereKey($candidate->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($team->TM_CONo === $actor->US_CONo && $candidate->US_CONo === $actor->US_CONo, 403);
            if (! $team->TMActive || $candidate->US_TMNo !== $team->getKey() || ! $this->access->eligible($candidate)) {
                throw ValidationException::withMessages(['pic' => 'Choose an active, fully onboarded member of this active team.']);
            }
            $assignment = $team->picAssignment()->lockForUpdate()->first();
            if ($assignment && $assignment->TP_USNo !== $candidate->getKey()) {
                $this->revokeDelegation($changes, User::query()->findOrFail($assignment->TP_USNo));
            }
            $assignment ? $changes->update($assignment, ['TP_USNo' => $candidate->getKey()]) : $changes->create(TeamPic::class, ['TP_TMNo' => $team->getKey(), 'TP_USNo' => $candidate->getKey()]);
            $role = Role::query()->where('RLCode', 'team_pic')->first()
                ?? $changes->create(Role::class, ['RLCode' => 'team_pic', 'RLName' => 'Team PIC']);
            if (! $candidate->roleAssignments()->where('UR_RLNo', $role->getKey())->exists()) {
                $changes->create(UserRole::class, ['UR_USNo' => $candidate->getKey(), 'UR_RLNo' => $role->getKey()]);
            }
        });
    }

    public function updateUser(User $actor, User $target, array $data): void
    {
        $this->writer->transaction($actor, function (FoundationChanges $changes) use ($actor, $target, $data) {
            $actor = $this->administrator($actor);
            $target = User::query()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($target->US_CONo === $actor->US_CONo, 403);
            $data = Validator::make($data, ['name' => ['required', 'string', 'max:150'], 'team' => ['nullable', 'string', 'max:10'], 'status' => ['required', Rule::in(['active', 'deactivated'])]])->validate();
            $teamNo = $data['team'] ?? null;
            if ($teamNo) {
                $team = Team::query()->whereKey($teamNo)->where('TM_CONo', $actor->US_CONo)->where('TMActive', true)->lockForUpdate()->first();
                if (! $team) {
                    throw ValidationException::withMessages(['team' => 'Choose an active team in this company.']);
                }
            }
            if ($target->picAssignments()->exists() && ($target->US_TMNo !== $teamNo || $data['status'] === 'deactivated')) {
                throw ValidationException::withMessages(['team' => 'Assign a replacement PIC before transferring or deactivating the current PIC.']);
            }
            if ($data['status'] === 'active' && ! $this->access->eligible($target)) {
                $copy = clone $target;
                $copy->USStatus = 'active';
                if (! $this->access->eligible($copy)) {
                    throw ValidationException::withMessages(['status' => 'Complete approval, activation, and mailbox readiness before reactivation.']);
                }
            }
            if ($data['status'] === 'deactivated' && $this->access->administrator($target) && $target->USStatus === 'active') {
                $others = User::query()->where('US_CONo', $actor->US_CONo)->whereKeyNot($target->getKey())->where('USStatus', 'active')
                    ->whereHas('roleAssignments.role', fn ($q) => $q->where('RLCode', 'administrator'))->get();
                if (! $others->contains(fn ($user) => $this->access->eligible($user))) {
                    throw ValidationException::withMessages(['status' => 'The last active administrator cannot be deactivated.']);
                }
            }
            $changedAccess = $target->US_TMNo !== $teamNo || $target->USStatus !== $data['status'];
            if ($changedAccess) {
                $this->revokeDelegation($changes, $target);
                // Team-based sources must not silently resume after a transfer or reactivation.
                foreach ($target->accessGrants()->whereNotNull('AX_DLNo')->where('AXActive', true)->get() as $grant) {
                    $changes->update($grant, ['AXActive' => false]);
                }
            }
            $changes->update($target, ['USName' => $data['name'], 'US_TMNo' => $teamNo, 'USStatus' => $data['status'],
                'USDeactivatedAt' => $data['status'] === 'deactivated' ? ($target->USDeactivatedAt ?? now()) : null,
                'USAuthVersion' => $target->USAuthVersion + ($changedAccess ? 1 : 0)]);
        });
    }

    private function revokeDelegation(FoundationChanges $changes, User $user): void
    {
        foreach ($user->delegations()->where('DLActive', true)->get() as $delegation) {
            foreach ($delegation->grants()->where('AXActive', true)->get() as $grant) {
                $changes->update($grant, ['AXActive' => false]);
            }
            $changes->update($delegation, ['DLActive' => false]);
        }
    }
}
