<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\MorphTo;

class FoundationLog extends FoundationModel
{
    public const PREFIX = 'FL';

    public const AUDIT_FIELDS = [];

    protected $table = 'TRFoundationLog';

    protected $fillable = ['FLAction', 'FLTable', 'FLRecordNo', 'FLBefore', 'FLAfter'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'FLBefore' => 'array',
            'FLAfter' => 'array',
        ]);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'FLTable', 'FLRecordNo');
    }
}
