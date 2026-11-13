<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRole extends FoundationModel
{
    public const PREFIX = 'UR';

    public const AUDIT_FIELDS = ['UR_USNo', 'UR_RLNo'];

    protected $table = 'TRUserRole';

    protected $fillable = ['UR_USNo', 'UR_RLNo'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'UR_USNo', 'USNo');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'UR_RLNo', 'RLNo');
    }
}
