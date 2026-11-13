<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activation extends FoundationModel
{
    public const PREFIX = 'AV';

    public const AUDIT_FIELDS = ['AV_USNo', 'AVExpiresAt', 'AVUsedAt', 'AVRevokedAt'];

    protected $table = 'TRActivation';

    protected $fillable = ['AV_USNo', 'AVTokenHash', 'AVExpiresAt', 'AVUsedAt', 'AVRevokedAt'];

    protected $hidden = ['AVTokenHash'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'AVExpiresAt' => 'datetime',
            'AVUsedAt' => 'datetime',
            'AVRevokedAt' => 'datetime',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'AV_USNo', 'USNo');
    }
}
