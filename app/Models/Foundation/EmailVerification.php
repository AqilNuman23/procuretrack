<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailVerification extends FoundationModel
{
    public const PREFIX = 'EV';

    public const AUDIT_FIELDS = ['EV_USNo', 'EV_RQNo', 'EVExpiresAt', 'EVUsedAt'];

    protected $table = 'TREmailVerification';

    protected $fillable = ['EV_USNo', 'EV_RQNo', 'EVTokenHash', 'EVNewEmail', 'EVAuthVersion', 'EVRecoveryVersion', 'EVExpiresAt', 'EVUsedAt'];

    protected $hidden = ['EVTokenHash', 'EVNewEmail'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'EVNewEmail' => 'encrypted',
            'EVAuthVersion' => 'integer',
            'EVRecoveryVersion' => 'integer',
            'EVExpiresAt' => 'datetime',
            'EVUsedAt' => 'datetime',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'EV_USNo', 'USNo');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(RecoveryRequest::class, 'EV_RQNo', 'RQNo');
    }
}
