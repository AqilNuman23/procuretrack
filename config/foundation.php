<?php

use App\Models\Foundation\AccessGrant;
use App\Models\Foundation\AccessGroup;
use App\Models\Foundation\Activation;
use App\Models\Foundation\Company;
use App\Models\Foundation\CompanySetting;
use App\Models\Foundation\Delegation;
use App\Models\Foundation\Department;
use App\Models\Foundation\EmailHistory;
use App\Models\Foundation\EmailVerification;
use App\Models\Foundation\FoundationError;
use App\Models\Foundation\FoundationLog;
use App\Models\Foundation\GroupItem;
use App\Models\Foundation\GroupMembership;
use App\Models\Foundation\Invitation;
use App\Models\Foundation\Mailbox;
use App\Models\Foundation\Module;
use App\Models\Foundation\NumberSequence;
use App\Models\Foundation\PasswordReset;
use App\Models\Foundation\Permission;
use App\Models\Foundation\RecoveryRequest;
use App\Models\Foundation\Review;
use App\Models\Foundation\Role;
use App\Models\Foundation\RolePermission;
use App\Models\Foundation\Team;
use App\Models\Foundation\TeamPic;
use App\Models\Foundation\User;
use App\Models\Foundation\UserRole;

return [
    'company_code' => env('FOUNDATION_COMPANY_CODE', 'DEMO'),
    // MS/TR table aliases also identify history subjects without storing class names.
    'models' => [
        'MSModule' => Module::class,
        'MSAccessGroup' => AccessGroup::class,
        'TRGroupItem' => GroupItem::class,
        'TRDelegation' => Delegation::class,
        'TRGroupMembership' => GroupMembership::class,
        'TRAccessGrant' => AccessGrant::class,
        'TRRecoveryRequest' => RecoveryRequest::class,
        'TRPasswordReset' => PasswordReset::class,
        'TREmailVerification' => EmailVerification::class,
        'TREmailHistory' => EmailHistory::class,
        'MSCompany' => Company::class,
        'MSCompanySetting' => CompanySetting::class,
        'MSTeam' => Team::class,
        'MSUser' => User::class,
        'TRTeamPIC' => TeamPic::class,
        'MSRole' => Role::class,
        'MSPermission' => Permission::class,
        'TRUserRole' => UserRole::class,
        'TRRolePermission' => RolePermission::class,
        'TRInvitation' => Invitation::class,
        'TRReview' => Review::class,
        'TRMailbox' => Mailbox::class,
        'TRActivation' => Activation::class,
        'MSDepartment' => Department::class,
        'TRFoundationLog' => FoundationLog::class,
        'TRFoundationError' => FoundationError::class,
        'MSNumberSequence' => NumberSequence::class,
    ],
];
