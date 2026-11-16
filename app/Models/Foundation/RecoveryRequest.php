<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecoveryRequest extends FoundationModel
{
    public const PREFIX = 'RQ';

    public const AUDIT_FIELDS = ['RQ_USNo', 'RQReviewer_USNo', 'RQStatus', 'RQVerificationMethod', 'RQReviewedAt', 'RQExpiresAt'];

    protected $table = 'TRRecoveryRequest';

    protected $fillable = ['RQ_USNo', 'RQReviewer_USNo', 'RQStatus', 'RQNewEmail', 'RQAuthVersion', 'RQRecoveryVersion', 'RQVerificationMethod', 'RQReason', 'RQReviewedAt', 'RQExpiresAt'];

    protected $hidden = ['RQNewEmail', 'RQReason'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'RQNewEmail' => 'encrypted',
            'RQReason' => 'encrypted',
            'RQAuthVersion' => 'integer',
            'RQRecoveryVersion' => 'integer',
            'RQReviewedAt' => 'datetime',
            'RQExpiresAt' => 'datetime',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'RQ_USNo', 'USNo');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'RQReviewer_USNo', 'USNo');
    }

    public function resets(): HasMany
    {
        return $this->hasMany(PasswordReset::class, 'PS_RQNo', 'RQNo');
    }
}
