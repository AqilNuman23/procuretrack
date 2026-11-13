<?php

namespace App\Models\Foundation;

class NumberSequence extends FoundationModel
{
    public const PREFIX = 'NS';

    public const AUDIT_FIELDS = [];

    protected $table = 'MSNumberSequence';

    protected $fillable = [];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'NSLastValue' => 'integer',
        ]);
    }
}
