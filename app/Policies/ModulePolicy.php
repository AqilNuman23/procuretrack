<?php

namespace App\Policies;

use App\Models\Foundation\Module;
use App\Models\Foundation\User;
use App\Services\Access\ModuleAccess;

class ModulePolicy
{
    public function __construct(private ModuleAccess $access) {}

    public function view(User $user, Module $module): bool
    {
        return $this->access->can($user, $module);
    }

    public function edit(User $user, Module $module): bool
    {
        return $this->access->can($user, $module, 'edit');
    }
}
