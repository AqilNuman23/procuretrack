<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delegation extends FoundationModel
{
    public const PREFIX = 'DL';

    public const AUDIT_FIELDS = ['DL_USNo', 'DL_MONo', 'DLMaxLevel', 'DLActive'];

    protected $table = 'TRDelegation';

    protected $fillable = ['DL_USNo', 'DL_MONo', 'DLMaxLevel', 'DLActive'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'DLActive' => 'boolean',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'DL_USNo', 'USNo');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'DL_MONo', 'MONo');
    }

    public function grants(): HasMany
    {
        return $this->hasMany(AccessGrant::class, 'AX_DLNo', 'DLNo');
    }
}
