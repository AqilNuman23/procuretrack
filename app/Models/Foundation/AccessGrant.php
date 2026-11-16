<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessGrant extends FoundationModel
{
    public const PREFIX = 'AX';

    public const AUDIT_FIELDS = ['AX_USNo', 'AX_MONo', 'AXGrantor_USNo', 'AX_DLNo', 'AX_GMNo', 'AXLevel', 'AXActive'];

    protected $table = 'TRAccessGrant';

    protected $fillable = ['AX_USNo', 'AX_MONo', 'AXGrantor_USNo', 'AX_DLNo', 'AX_GMNo', 'AXLevel', 'AXActive'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'AXActive' => 'boolean',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'AX_USNo', 'USNo');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'AX_MONo', 'MONo');
    }

    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'AXGrantor_USNo', 'USNo');
    }

    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class, 'AX_DLNo', 'DLNo');
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(GroupMembership::class, 'AX_GMNo', 'GMNo');
    }
}
