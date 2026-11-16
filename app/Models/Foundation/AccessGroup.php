<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessGroup extends FoundationModel
{
    public const PREFIX = 'AG';

    public const AUDIT_FIELDS = ['AG_CONo', 'AGName', 'AGActive'];

    protected $table = 'MSAccessGroup';

    protected $fillable = ['AG_CONo', 'AGName', 'AGActive'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'AGActive' => 'boolean',
        ]);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'AG_CONo', 'CONo');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GroupItem::class, 'GI_AGNo', 'AGNo');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(GroupMembership::class, 'GM_AGNo', 'AGNo');
    }
}
