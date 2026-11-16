<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordReset extends FoundationModel
{
    public const PREFIX = 'PS';

    public const AUDIT_FIELDS = ['PS_USNo', 'PS_RQNo', 'PSExpiresAt', 'PSUsedAt', 'PSRevokedAt'];

    protected $table = 'TRPasswordReset';

    protected $fillable = ['PS_USNo', 'PS_RQNo', 'PSTokenHash', 'PSAuthVersion', 'PSRecoveryVersion', 'PSExpiresAt', 'PSUsedAt', 'PSRevokedAt'];

    protected $hidden = ['PSTokenHash'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'PSAuthVersion' => 'integer',
            'PSRecoveryVersion' => 'integer',
            'PSExpiresAt' => 'datetime',
            'PSUsedAt' => 'datetime',
            'PSRevokedAt' => 'datetime',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'PS_USNo', 'USNo');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(RecoveryRequest::class, 'PS_RQNo', 'RQNo');
    }
}
