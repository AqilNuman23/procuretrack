<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends FoundationModel
{
    public const PREFIX = 'RV';

    public const AUDIT_FIELDS = ['RV_IVNo', 'RV_USNo', 'RVDecision', 'RVDecidedAt'];

    protected $table = 'TRReview';

    protected $fillable = ['RV_IVNo', 'RV_USNo', 'RVDecision', 'RVReason', 'RVDecidedAt'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'RVDecidedAt' => 'datetime',
        ]);
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class, 'RV_IVNo', 'IVNo');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'RV_USNo', 'USNo');
    }
}
