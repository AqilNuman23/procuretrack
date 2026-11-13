<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invitation extends FoundationModel
{
    public const PREFIX = 'IV';

    public const AUDIT_FIELDS = ['IV_TMNo', 'IVInviter_USNo', 'IV_USNo', 'IVStatus', 'IVExpiresAt', 'IVAcceptedAt', 'IVRevokedAt'];

    protected $table = 'TRInvitation';

    protected $fillable = ['IV_TMNo', 'IVInviter_USNo', 'IV_USNo', 'IVName', 'IVEmail', 'IVWorkerID', 'IVStatus', 'IVTokenHash', 'IVExpiresAt', 'IVAcceptedAt', 'IVRevokedAt'];

    protected $hidden = ['IVTokenHash'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'IVExpiresAt' => 'datetime',
            'IVAcceptedAt' => 'datetime',
            'IVRevokedAt' => 'datetime',
        ]);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'IV_TMNo', 'TMNo');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'IVInviter_USNo', 'USNo');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'IV_USNo', 'USNo');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'RV_IVNo', 'IVNo');
    }

    public function mailboxes(): HasMany
    {
        return $this->hasMany(Mailbox::class, 'MX_IVNo', 'IVNo');
    }
}
