<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupMembership extends FoundationModel
{
    public const PREFIX = 'GM';

    public const AUDIT_FIELDS = ['GM_USNo', 'GM_AGNo', 'GMGrantor_USNo', 'GMActive'];

    protected $table = 'TRGroupMembership';

    protected $fillable = ['GM_USNo', 'GM_AGNo', 'GMGrantor_USNo', 'GMActive'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'GMActive' => 'boolean',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'GM_USNo', 'USNo');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AccessGroup::class, 'GM_AGNo', 'AGNo');
    }

    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'GMGrantor_USNo', 'USNo');
    }

    public function grants(): HasMany
    {
        return $this->hasMany(AccessGrant::class, 'AX_GMNo', 'GMNo');
    }
}
