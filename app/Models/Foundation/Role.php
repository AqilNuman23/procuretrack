<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends FoundationModel
{
    public const PREFIX = 'RL';

    public const AUDIT_FIELDS = ['RLCode', 'RLName'];

    protected $table = 'MSRole';

    protected $fillable = ['RLCode', 'RLName'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
        ]);
    }

    public function userAssignments(): HasMany
    {
        return $this->hasMany(UserRole::class, 'UR_RLNo', 'RLNo');
    }

    public function permissionAssignments(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'RP_RLNo', 'RLNo');
    }
}
