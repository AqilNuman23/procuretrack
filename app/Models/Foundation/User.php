<?php

namespace App\Models\Foundation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends FoundationModel
{
    public const PREFIX = 'US';

    public const AUDIT_FIELDS = ['US_CONo', 'US_TMNo', 'USName', 'USStatus', 'USActivatedAt', 'USDeactivatedAt'];

    protected $table = 'MSUser';

    protected $fillable = ['US_CONo', 'US_TMNo', 'USName', 'USWorkerID', 'USContactEmail', 'USEmail', 'USPassword', 'USStatus', 'USActivatedAt', 'USDeactivatedAt'];

    protected $hidden = ['USPassword'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'USPassword' => 'hashed',
            'USActivatedAt' => 'datetime',
            'USDeactivatedAt' => 'datetime',
        ]);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'US_CONo', 'CONo');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'US_TMNo', 'TMNo');
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(UserRole::class, 'UR_USNo', 'USNo');
    }

    public function picAssignments(): HasMany
    {
        return $this->hasMany(TeamPic::class, 'TP_USNo', 'USNo');
    }

    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'IVInviter_USNo', 'USNo');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'IV_USNo', 'USNo');
    }

    public function mailbox(): HasOne
    {
        return $this->hasOne(Mailbox::class, 'MX_USNo', 'USNo');
    }

    public function activations(): HasMany
    {
        return $this->hasMany(Activation::class, 'AV_USNo', 'USNo');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'RV_USNo', 'USNo');
    }
}
