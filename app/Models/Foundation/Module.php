<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends FoundationModel
{
    public const PREFIX = 'MO';

    public const AUDIT_FIELDS = ['MOCode', 'MOName', 'MOParent_MONo', 'MODelegable', 'MOActive'];

    protected $table = 'MSModule';

    protected $fillable = ['MOCode', 'MOName', 'MOParent_MONo', 'MODelegable', 'MOActive'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'MOActive' => 'boolean',
            'MODelegable' => 'boolean',
        ]);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'MOParent_MONo', 'MONo');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Module::class, 'MOParent_MONo', 'MONo');
    }

    public function delegations(): HasMany
    {
        return $this->hasMany(Delegation::class, 'DL_MONo', 'MONo');
    }
}
