<?php

namespace App\Services\Access;

use App\Models\Foundation\AccessGrant;
use App\Models\Foundation\AccessGroup;
use App\Models\Foundation\Delegation;
use App\Models\Foundation\GroupItem;
use App\Models\Foundation\GroupMembership;
use App\Models\Foundation\Module;
use App\Models\Foundation\User;
use App\Support\Foundation\FoundationChanges;
use App\Support\Foundation\FoundationWriter;
use Illuminate\Support\Collection;

class AccessManager
{
    public function __construct(private FoundationWriter $writer, private ModuleAccess $access) {}

    public function group(User $actor, string $name, array $entries): AccessGroup
    {
        return $this->writer->transaction($actor, function (FoundationChanges $changes) use ($actor, $name, $entries) {
            $actor = $this->lockActor($actor);
            abort_unless($this->access->administrator($actor), 403);
            abort_if(! $entries, 422, 'Select at least one submodule.');
            $group = $changes->create(AccessGroup::class, ['AG_CONo' => $actor->US_CONo, 'AGName' => $name]);
            foreach ($entries as $entry) {
                $module = Module::query()->findOrFail($entry['module']);
                abort_unless($module->MOActive && $module->MOParent_MONo && in_array($entry['level'], ['read', 'edit']), 422);
                abort_if(in_array($module->MOCode, ['settings.access', 'people.recovery', 'people.history']), 422, 'Administrative actions are separate from module-level grants.');
                $changes->create(GroupItem::class, ['GI_AGNo' => $group->getKey(), 'GI_MONo' => $module->getKey(), 'GILevel' => $entry['level']]);
            }

            return $group;
        });
    }

    public function delegation(User $actor, User $pic, Module $module, string $level, bool $active): Delegation
    {
        return $this->writer->transaction($actor, function (FoundationChanges $changes) use ($actor, $pic, $module, $level, $active) {
            $actor = $this->lockActor($actor);
            $pic = User::query()->whereKey($pic->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($this->access->administrator($actor) && $pic->US_CONo === $actor->US_CONo, 403);
            $existing = Delegation::query()->where('DL_USNo', $pic->getKey())->where('DL_MONo', $module->getKey())->lockForUpdate()->first();
            abort_unless(in_array($level, ['read', 'edit']), 422);
            if ($active) {
                abort_unless($this->access->pic($pic) && $this->access->eligible($pic), 422, 'Select an active team PIC.');
                abort_unless($module->MOActive && $module->MODelegable && ! $module->MOParent_MONo, 422);
            } else {
                abort_unless($existing, 422);
            }
            $values = ['DLMaxLevel' => $level, 'DLActive' => $active];
            $delegation = $existing ? $changes->update($existing, $values) : $changes->create(Delegation::class, $values + ['DL_USNo' => $pic->getKey(), 'DL_MONo' => $module->getKey()]);
            foreach ($delegation->grants()->where('AXActive', true)->get() as $grant) {
                if (! $active) {
                    $changes->update($grant, ['AXActive' => false]);
                } elseif ($level === 'read' && $grant->AXLevel === 'edit') {
                    $changes->update($grant, ['AXLevel' => 'read']);
                }
            }

            return $delegation;
        });
    }

    public function direct(User $actor, array $userNos, Module $module, string $level): void
    {
        $this->writer->transaction($actor, function (FoundationChanges $changes) use ($actor, $userNos, $module, $level) {
            $actor = $this->lockActor($actor);
            foreach ($this->targets($actor, $userNos) as $target) {
                $delegation = $this->authority($actor, $target, $module, $level);
                $identity = ['AX_USNo' => $target->getKey(), 'AX_MONo' => $module->getKey(), 'AXGrantor_USNo' => $actor->getKey(), 'AX_GMNo' => null];
                $existing = AccessGrant::query()->where($identity)->lockForUpdate()->first();
                $values = ['AXLevel' => $level, 'AXActive' => true, 'AX_DLNo' => $delegation?->getKey()];
                $existing ? $changes->update($existing, $values) : $changes->create(AccessGrant::class, $identity + $values);
            }
        });
    }

    public function assignGroup(User $actor, array $userNos, AccessGroup $group): void
    {
        $this->writer->transaction($actor, function (FoundationChanges $changes) use ($actor, $userNos, $group) {
            $actor = $this->lockActor($actor);
            $group = AccessGroup::query()->whereKey($group->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($group->AGActive && $group->AG_CONo === $actor->US_CONo, 403);
            foreach ($this->targets($actor, $userNos) as $target) {
                $identity = ['GM_USNo' => $target->getKey(), 'GM_AGNo' => $group->getKey(), 'GMGrantor_USNo' => $actor->getKey()];
                $existing = GroupMembership::query()->where($identity)->lockForUpdate()->first();
                $membership = $existing ? $changes->update($existing, ['GMActive' => true]) : $changes->create(GroupMembership::class, $identity);
                foreach ($group->items()->where('GIActive', true)->with('module')->get() as $item) {
                    $delegation = $this->authority($actor, $target, $item->module, $item->GILevel);
                    $grant = AccessGrant::query()->where('AX_GMNo', $membership->getKey())->where('AX_MONo', $item->GI_MONo)->lockForUpdate()->first();
                    $values = ['AXLevel' => $item->GILevel, 'AXActive' => true, 'AX_DLNo' => $delegation?->getKey()];
                    $grant ? $changes->update($grant, $values) : $changes->create(AccessGrant::class, $values + [
                        'AX_USNo' => $target->getKey(), 'AX_MONo' => $item->GI_MONo, 'AXGrantor_USNo' => $actor->getKey(), 'AX_GMNo' => $membership->getKey(),
                    ]);
                }
            }
        });
    }

    public function revoke(User $actor, AccessGrant $grant): void
    {
        $this->writer->transaction($actor, function (FoundationChanges $changes) use ($actor, $grant) {
            $actor = $this->lockActor($actor);
            $grant = AccessGrant::query()->whereKey($grant->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($grant->user->US_CONo === $actor->US_CONo, 403);
            if (! $this->access->administrator($actor)) {
                abort_unless($this->access->pic($actor) && $grant->AXGrantor_USNo === $actor->getKey() && $grant->AX_DLNo
                    && $grant->user->US_TMNo === $actor->US_TMNo, 403);
            }
            $changes->update($grant, ['AXActive' => false]);
        });
    }

    public function deactivateGroup(User $actor, AccessGroup $group): void
    {
        $this->writer->transaction($actor, function (FoundationChanges $changes) use ($actor, $group) {
            $actor = $this->lockActor($actor);
            abort_unless($this->access->administrator($actor) && $group->AG_CONo === $actor->US_CONo, 403);
            $changes->update($group, ['AGActive' => false]);
            foreach ($group->memberships()->where('GMActive', true)->get() as $membership) {
                $changes->update($membership, ['GMActive' => false]);
                foreach ($membership->grants()->where('AXActive', true)->get() as $grant) {
                    $changes->update($grant, ['AXActive' => false]);
                }
            }
        });
    }

    public function revokeMembership(User $actor, GroupMembership $membership): void
    {
        $this->writer->transaction($actor, function (FoundationChanges $changes) use ($actor, $membership) {
            $actor = $this->lockActor($actor);
            $membership = GroupMembership::query()->whereKey($membership->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($membership->user->US_CONo === $actor->US_CONo && $membership->group->AG_CONo === $actor->US_CONo, 403);
            if (! $this->access->administrator($actor)) {
                abort_unless($this->access->pic($actor)
                    && $membership->GMGrantor_USNo === $actor->getKey() && $membership->user->US_TMNo === $actor->US_TMNo, 403);
            }
            $changes->update($membership, ['GMActive' => false]);
            foreach ($membership->grants()->where('AXActive', true)->get() as $grant) {
                $changes->update($grant, ['AXActive' => false]);
            }
        });
    }

    private function lockActor(User $actor): User
    {
        $actor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
        abort_unless($this->access->eligible($actor) && ($this->access->administrator($actor) || $this->access->pic($actor)), 403);

        return $actor;
    }

    private function targets(User $actor, array $userNos): Collection
    {
        $userNos = array_values(array_unique($userNos));
        abort_if(! $userNos, 422);
        $targets = User::query()->whereIn('USNo', $userNos)->orderBy('USNo')->lockForUpdate()->get();
        abort_unless($targets->count() === count($userNos), 403);
        foreach ($targets as $target) {
            abort_unless($target->US_CONo === $actor->US_CONo, 403);
            if (! $this->access->administrator($actor)) {
                abort_unless($target->US_TMNo === $actor->US_TMNo
                    && $target->getKey() !== $actor->getKey() && ! $this->access->administrator($target) && ! $this->access->pic($target), 403);
            }
        }

        return $targets;
    }

    private function authority(User $actor, User $target, Module $module, string $level): ?Delegation
    {
        abort_unless($module->MOActive && $module->MOParent_MONo && $module->parent?->MOActive && in_array($level, ['read', 'edit']), 422);
        abort_if(in_array($module->MOCode, ['settings.access', 'people.recovery', 'people.history']), 422, 'Administrative actions are separate from module-level grants.');
        if ($this->access->administrator($actor)) {
            return null;
        }
        abort_unless($this->access->pic($actor) && $module->MODelegable && $target->US_TMNo === $actor->US_TMNo, 403);
        $delegation = $actor->delegations()->where('DL_MONo', $module->MOParent_MONo)->where('DLActive', true)->lockForUpdate()->first();
        abort_unless($delegation && $this->access->rank($level) <= $this->access->rank($delegation->DLMaxLevel), 403, 'This assignment exceeds your delegated authority.');

        return $delegation;
    }
}
