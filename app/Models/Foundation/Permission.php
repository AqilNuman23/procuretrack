<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Permission extends FoundationModel
{
    public const PREFIX = 'PM';

    public const AUDIT_FIELDS = ['PMCode', 'PMName'];

    protected $table = 'MSPermission';

    protected $fillable = ['PMCode', 'PMName'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
        ]);
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'RP_PMNo', 'PMNo');
    }
}
