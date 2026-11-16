<?php

namespace App\Models\Foundation;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;

class User extends FoundationModel implements AuthenticatableContract
{
    use Authenticatable, Notifiable;

    public const PREFIX = 'US';

    public const AUDIT_FIELDS = ['US_CONo', 'US_TMNo', 'USName', 'USStatus', 'USActivatedAt', 'USDeactivatedAt', 'USApprovedAt', 'USAuthVersion', 'USRecoveryVersion', 'USLocalDemo', 'USLoginName'];

    protected $table = 'MSUser';

    protected $fillable = ['US_CONo', 'US_TMNo', 'USName', 'USWorkerID', 'USContactEmail', 'USEmail', 'USPassword', 'USStatus', 'USActivatedAt', 'USDeactivatedAt', 'USRecoveryEmail', 'USLoginName', 'USApprovedAt', 'USAuthVersion', 'USRecoveryVersion', 'USLocalDemo'];

    protected $hidden = ['USPassword', 'USContactEmail', 'USRecoveryEmail'];

    public function getAuthPasswordName(): string
    {
        return 'USPassword';
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function routeNotificationForMail(): ?string
    {
        return $this->USEmail;
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'USPassword' => 'hashed',
            'USAuthVersion' => 'integer',
            'USRecoveryVersion' => 'integer',
            'USLocalDemo' => 'boolean',
            'USApprovedAt' => 'datetime',
            'USActivatedAt' => 'datetime',
            'USDeactivatedAt' => 'datetime',
        ]);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'US_CONo', 'CONo');
    }

    public function accessGrants(): HasMany
    {
        return $this->hasMany(AccessGrant::class, 'AX_USNo', 'USNo');
    }

    public function delegations(): HasMany
    {
        return $this->hasMany(Delegation::class, 'DL_USNo', 'USNo');
    }

    public function groupMemberships(): HasMany
    {
        return $this->hasMany(GroupMembership::class, 'GM_USNo', 'USNo');
    }

    public function recoveryRequests(): HasMany
    {
        return $this->hasMany(RecoveryRequest::class, 'RQ_USNo', 'USNo');
    }

    public function emailHistory(): HasMany
    {
        return $this->hasMany(EmailHistory::class, 'EH_USNo', 'USNo');
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
