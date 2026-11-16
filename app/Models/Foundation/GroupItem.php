<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupItem extends FoundationModel
{
    public const PREFIX = 'GI';

    public const AUDIT_FIELDS = ['GI_AGNo', 'GI_MONo', 'GILevel', 'GIActive'];

    protected $table = 'TRGroupItem';

    protected $fillable = ['GI_AGNo', 'GI_MONo', 'GILevel', 'GIActive'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'GIActive' => 'boolean',
        ]);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AccessGroup::class, 'GI_AGNo', 'AGNo');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'GI_MONo', 'MONo');
    }
}
