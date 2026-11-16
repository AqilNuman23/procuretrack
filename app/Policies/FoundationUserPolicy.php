<?php

namespace App\Policies;

use App\Models\Foundation\User;
use App\Services\Access\ModuleAccess;

class FoundationUserPolicy
{
    public function __construct(private ModuleAccess $access) {}

    public function view(User $actor, User $target): bool
    {
        return $this->access->eligible($actor) && $actor->US_CONo === $target->US_CONo && ($actor->getKey() === $target->getKey()
            || $this->access->administrator($actor) || ($this->access->pic($actor) && $actor->US_TMNo === $target->US_TMNo));
    }
}
