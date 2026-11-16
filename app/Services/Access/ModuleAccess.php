<?php

namespace App\Services\Access;

use App\Models\Foundation\AccessGrant;
use App\Models\Foundation\Module;
use App\Models\Foundation\User;
use Illuminate\Database\Eloquent\Builder;

class ModuleAccess
{
    public function eligible(User $user): bool
    {
        if ($user->USStatus !== 'active' || ! $user->USActivatedAt || ! $user->USApprovedAt || ! $user->USPassword) {
            return false;
        }
        if ($user->USLocalDemo && ! app()->environment('local')) {
            return false;
        }
        $mailbox = $user->mailbox()->first();

        return $mailbox && $mailbox->MXStatus === 'ready' && (! $mailbox->MXIsMock || app()->environment(['local', 'testing']));
    }

    public function administrator(User $user): bool
    {
        return $user->roleAssignments()->whereHas('role', fn (Builder $q) => $q->where('RLCode', 'administrator'))->exists();
    }

    public function pic(User $user): bool
    {
        return $user->US_TMNo && $user->picAssignments()->where('TP_TMNo', $user->US_TMNo)->exists()
            && $user->roleAssignments()->whereHas('role', fn (Builder $q) => $q->where('RLCode', 'team_pic'))->exists();
    }

    public function can(User $user, Module|string $module, string $level = 'read'): bool
    {
        if (is_string($module)) {
            $module = Module::query()->where('MOCode', $module)->first();
        }
        if (! $module || ! array_key_exists($module->MOCode, config('modules')) || ! $module->MOActive || ! $this->eligible($user)) {
            return false;
        }
        if ($module->MOParent_MONo && ! $module->parent?->MOActive) {
            return false;
        }
        if ($this->administrator($user)) {
            return true;
        }
        if (in_array($module->MOCode, ['settings.access', 'people.recovery']) && ! $this->pic($user)) {
            return false;
        }
        if ($module->MOCode === 'people.history') {
            return false;
        }
        if (! $module->MOParent_MONo) {
            return $level === 'read' && $module->children()->get()->contains(fn ($child) => $this->can($user, $child));
        }
        $mode = 0;
        if ($module->MOCode === 'help.manual') {
            $mode = 1;
        }
        if ($this->pic($user)) {
            if (in_array($module->MOCode, ['people.users', 'people.recovery', 'settings.access'])) {
                $mode = 1;
            }
            if ($module->MOCode === 'settings.access') {
                $mode = 2;
            }
            if ($module->MODelegable) {
                $delegation = $user->delegations()->where('DL_MONo', $module->MOParent_MONo)->where('DLActive', true)->first();
                if ($delegation) {
                    $mode = max($mode, $this->rank($delegation->DLMaxLevel));
                }
            }
        }
        foreach ($user->accessGrants()->with(['delegation.user', 'membership.group.items'])->where('AX_MONo', $module->getKey())->where('AXActive', true)->get() as $grant) {
            $mode = max($mode, $this->grantRank($user, $module, $grant));
        }

        return $mode >= $this->rank($level);
    }

    public function grantRank(User $target, Module $module, AccessGrant $grant): int
    {
        $rank = $this->rank($grant->AXLevel);
        if ($grant->AX_GMNo) {
            $membership = $grant->membership;
            if (! $membership?->GMActive || ! $membership->group?->AGActive || $membership->group->AG_CONo !== $target->US_CONo
                || $membership->GM_USNo !== $target->getKey() || $membership->GMGrantor_USNo !== $grant->AXGrantor_USNo) {
                return 0;
            }
            $item = $membership->group->items->first(fn ($item) => $item->GI_MONo === $module->getKey() && $item->GIActive);
            if (! $item) {
                return 0;
            }
            $rank = min($rank, $this->rank($item->GILevel));
        }
        if ($grant->AX_DLNo) {
            $delegation = $grant->delegation;
            $pic = $delegation?->user;
            if (! $delegation?->DLActive || ! $pic || ! $this->eligible($pic) || ! $this->pic($pic)
                || $pic->getKey() !== $grant->AXGrantor_USNo || $pic->getKey() === $target->getKey()
                || ! $module->MODelegable || $delegation->DL_MONo !== $module->MOParent_MONo
                || $pic->US_CONo !== $target->US_CONo || $pic->US_TMNo !== $target->US_TMNo
                || $this->administrator($target) || $this->pic($target)) {
                return 0;
            }
            $rank = min($rank, $this->rank($delegation->DLMaxLevel));
        }

        return $rank;
    }

    public function rank(string $level): int
    {
        return match ($level) {
            'read' => 1, 'edit' => 2, default => PHP_INT_MAX
        };
    }

    public function visibleUsers(User $actor): Builder
    {
        $query = User::query()->where('US_CONo', $actor->US_CONo);
        if ($this->administrator($actor)) {
            return $query;
        }

        return $this->pic($actor) ? $query->where('US_TMNo', $actor->US_TMNo) : $query->whereKey($actor->getKey());
    }
}
