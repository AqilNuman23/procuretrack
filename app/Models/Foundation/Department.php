<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends FoundationModel
{
    public const PREFIX = 'DP';

    public const AUDIT_FIELDS = ['DP_CONo', 'DPCode', 'DPName', 'DPActive'];

    protected $table = 'MSDepartment';

    protected $fillable = ['DP_CONo', 'DPCode', 'DPName', 'DPActive'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'DPActive' => 'boolean',
        ]);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'DP_CONo', 'CONo');
    }
}
