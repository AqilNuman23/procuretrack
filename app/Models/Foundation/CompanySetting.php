<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends FoundationModel
{
    public const PREFIX = 'CS';

    public const AUDIT_FIELDS = ['CS_CONo', 'CSKey'];

    protected $table = 'MSCompanySetting';

    protected $fillable = ['CS_CONo', 'CSKey', 'CSValue'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'CSValue' => 'array',
        ]);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'CS_CONo', 'CONo');
    }
}
