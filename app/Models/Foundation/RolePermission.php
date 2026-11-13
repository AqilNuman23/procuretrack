<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends FoundationModel
{
    public const PREFIX = 'RP';

    public const AUDIT_FIELDS = ['RP_RLNo', 'RP_PMNo'];

    protected $table = 'TRRolePermission';

    protected $fillable = ['RP_RLNo', 'RP_PMNo'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
        ]);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'RP_RLNo', 'RLNo');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'RP_PMNo', 'PMNo');
    }
}
