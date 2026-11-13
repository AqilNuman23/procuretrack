<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mailbox extends FoundationModel
{
    public const PREFIX = 'MX';

    public const AUDIT_FIELDS = ['MX_USNo', 'MX_IVNo', 'MXProvider', 'MXIsMock', 'MXStatus', 'MXFailureCode', 'MXAttempts', 'MXReadyAt'];

    protected $table = 'TRMailbox';

    protected $fillable = ['MX_USNo', 'MX_IVNo', 'MXEmail', 'MXProvider', 'MXIsMock', 'MXStatus', 'MXIdempotencyKey', 'MXProviderReference', 'MXFailureCode', 'MXAttempts', 'MXReadyAt'];

    protected $hidden = ['MXIdempotencyKey', 'MXProviderReference'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'MXIsMock' => 'boolean',
            'MXAttempts' => 'integer',
            'MXReadyAt' => 'datetime',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'MX_USNo', 'USNo');
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class, 'MX_IVNo', 'IVNo');
    }
}
