<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Team extends FoundationModel
{
    public const PREFIX = 'TM';

    public const AUDIT_FIELDS = ['TM_CONo', 'TMCode', 'TMName', 'TMActive'];

    protected $table = 'MSTeam';

    protected $fillable = ['TM_CONo', 'TMCode', 'TMName', 'TMActive'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'TMActive' => 'boolean',
        ]);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'TM_CONo', 'CONo');
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'US_TMNo', 'TMNo');
    }

    public function picAssignment(): HasOne
    {
        return $this->hasOne(TeamPic::class, 'TP_TMNo', 'TMNo');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'IV_TMNo', 'TMNo');
    }
}
