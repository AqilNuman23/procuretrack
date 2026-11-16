<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailHistory extends FoundationModel
{
    public const PREFIX = 'EH';

    public const AUDIT_FIELDS = ['EH_USNo', 'EH_RQNo', 'EHApprover_USNo', 'EHVerifiedAt'];

    protected $table = 'TREmailHistory';

    protected $fillable = ['EH_USNo', 'EH_RQNo', 'EHApprover_USNo', 'EHOldEmail', 'EHNewEmail', 'EHVerifiedAt'];

    protected $hidden = ['EHOldEmail', 'EHNewEmail'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'EHOldEmail' => 'encrypted',
            'EHNewEmail' => 'encrypted',
            'EHVerifiedAt' => 'datetime',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'EH_USNo', 'USNo');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(RecoveryRequest::class, 'EH_RQNo', 'RQNo');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'EHApprover_USNo', 'USNo');
    }
}
