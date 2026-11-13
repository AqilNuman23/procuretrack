<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\MorphTo;

class FoundationError extends FoundationModel
{
    public const PREFIX = 'FE';

    public const AUDIT_FIELDS = [];

    protected $table = 'TRFoundationError';

    protected $fillable = ['FECorrelation', 'FEOperation', 'FETable', 'FERecordNo', 'FEException', 'FECode', 'FEMessage', 'FEIsExample'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'FEIsExample' => 'boolean',
        ]);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'FETable', 'FERecordNo');
    }
}
