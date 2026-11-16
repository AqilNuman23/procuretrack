<?php

namespace Database\Seeders;

use App\Models\Foundation\Activation;
use App\Models\Foundation\Company;
use App\Models\Foundation\CompanySetting;
use App\Models\Foundation\Department;
use App\Models\Foundation\FoundationError;
use App\Models\Foundation\FoundationModel;
use App\Models\Foundation\Invitation;
use App\Models\Foundation\Mailbox;
use App\Models\Foundation\Permission;
use App\Models\Foundation\Review;
use App\Models\Foundation\Role;
use App\Models\Foundation\RolePermission;
use App\Models\Foundation\Team;
use App\Models\Foundation\TeamPic;
use App\Models\Foundation\User;
use App\Models\Foundation\UserRole;
use App\Support\Foundation\FoundationWriter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class FoundationSeeder extends Seeder
{
    private FoundationWriter $writer;

    private ?User $actor = null;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Fictional foundation data may only be seeded locally or in tests.');
        }
        $this->writer = app(FoundationWriter::class);
        $company = $this->ensure(Company::class, ['COCode' => 'DEMO'], [
            'COName' => 'Meridian Works · fictional', 'COTimezone' => 'Asia/Kuala_Lumpur', 'COCurrency' => 'MYR',
        ]);
        $admin = $this->person($company, null, 'PT-1000', 'Alex Morgan', 'alex', 'active');
        $this->actor = $admin;

        foreach (['theme' => ['value' => 'dark'], 'mailbox_mode' => ['value' => 'mock'], 'language' => ['value' => 'en']] as $key => $value) {
            $this->ensure(CompanySetting::class, ['CS_CONo' => $company->getKey(), 'CSKey' => $key], ['CSValue' => $value]);
        }
        $teams = [];
        foreach (['OPS' => 'Operations', 'FIN' => 'Finance', 'TECH' => 'Technology'] as $code => $name) {
            $teams[$code] = $this->ensure(Team::class, ['TM_CONo' => $company->getKey(), 'TMCode' => $code], ['TMName' => $name]);
            $this->ensure(Department::class, ['DP_CONo' => $company->getKey(), 'DPCode' => $code], ['DPName' => $name]);
        }

        $roles = [];
        foreach (['administrator' => 'Administrator', 'team_pic' => 'Team PIC', 'member' => 'Team member'] as $code => $name) {
            $roles[$code] = $this->ensure(Role::class, ['RLCode' => $code], ['RLName' => $name]);
        }
        $permissionNames = [
            'teams.manage' => 'Manage teams', 'users.view' => 'View company accounts',
            'users.access' => 'Manage account access', 'reviews.decide' => 'Review applications',
            'mailboxes.manage' => 'Monitor mailbox provisioning', 'roles.manage' => 'Manage roles and permissions',
            'history.view' => 'View foundation history', 'settings.manage' => 'Manage company settings',
            'invitations.own_team' => 'Invite and track own-team members',
            'members.own_team' => 'View permitted own-team members', 'profile.own' => 'Maintain own profile',
        ];
        foreach ($permissionNames as $code => $name) {
            $permission = $this->ensure(Permission::class, ['PMCode' => $code], ['PMName' => $name]);
            $allowed = match ($code) {
                'profile.own' => ['administrator', 'team_pic', 'member'],
                'invitations.own_team', 'members.own_team' => ['team_pic'],
                default => ['administrator'],
            };
            foreach ($allowed as $role) {
                $this->ensure(RolePermission::class, ['RP_RLNo' => $roles[$role]->getKey(), 'RP_PMNo' => $permission->getKey()]);
            }
        }
        $this->assignRole($admin, $roles['administrator']);
        $this->mailbox($admin, 'ready');

        $pics = [];
        foreach ([['OPS', 'PT-1011', 'Farid Hassan', 'farid'], ['FIN', 'PT-1025', 'Mei Tan', 'mei'], ['TECH', 'PT-1038', 'Daniel Lim', 'daniel']] as [$code, $worker, $name, $email]) {
            $pics[$code] = $this->person($company, $teams[$code], $worker, $name, $email, 'active');
            $this->assignRole($pics[$code], $roles['team_pic']);
            $this->ensure(TeamPic::class, ['TP_TMNo' => $teams[$code]->getKey()], ['TP_USNo' => $pics[$code]->getKey()]);
            $this->mailbox($pics[$code], 'ready');
        }

        // Each row is a coherent scenario across invitation, review, mailbox, and activation.
        $scenarios = [
            ['OPS', 'PT-1042', 'Nur Aisyah Rahman', 'aisyah', 'awaiting_review', 'not_started', 'pending_activation'],
            ['OPS', 'PT-1041', 'Hana Ismail', 'hana', 'approved', 'failed', 'pending_activation'],
            ['FIN', 'PT-1040', 'Sofia Abdullah', 'sofia', 'approved', 'ready', 'pending_activation'],
            ['TECH', 'PT-1029', 'Arjun Kumar', 'arjun', 'approved', 'ready', 'deactivated'],
            ['OPS', 'PT-1043', 'Lina Zain', 'lina', 'approved', 'ready', 'active'],
            ['FIN', 'PT-1044', 'Omar Aziz', 'omar', 'approved', 'pending', 'pending_activation'],
        ];
        foreach ($scenarios as [$team, $worker, $name, $email, $review, $mailbox, $account]) {
            $person = $this->person($company, $teams[$team], $worker, $name, $email, $account, $review === 'approved');
            $this->assignRole($person, $roles['member']);
            $invitation = $this->invite($teams[$team], $pics[$team], $person, $worker, $name, $email, $review);
            if ($review === 'approved') {
                $this->ensure(Review::class, ['RV_IVNo' => $invitation->getKey(), 'RVDecision' => 'approved'], [
                    'RV_USNo' => $admin->getKey(), 'RVDecidedAt' => now()->subDay(),
                ]);
            }
            $this->mailbox($person, $mailbox, $invitation);
            if ($mailbox === 'ready') {
                $this->ensure(Activation::class, ['AV_USNo' => $person->getKey()], [
                    'AVTokenHash' => null, 'AVExpiresAt' => now()->addDays(2),
                    'AVUsedAt' => in_array($account, ['active', 'deactivated']) ? now()->subDay() : null,
                ]);
            }
        }
        foreach (['invited', 'expired', 'revoked', 'rejected'] as $index => $status) {
            $invitation = $this->invite($teams['OPS'], $pics['OPS'], null, 'PT-20'.$index, 'Example '.ucfirst($status), 'example-'.$status, $status);
            if ($status === 'rejected') {
                $this->ensure(Review::class, ['RV_IVNo' => $invitation->getKey(), 'RVDecision' => 'rejected'], [
                    'RV_USNo' => $admin->getKey(), 'RVReason' => 'Fictional example: details could not be confirmed.', 'RVDecidedAt' => now(),
                ]);
            }
        }

        if (! FoundationError::query()->where('FECode', 'DEMO_WRITE_FAILURE')->where('FEIsExample', true)->exists()) {
            DB::transaction(function () use ($admin) {
                $error = new FoundationError([
                    'FECorrelation' => (string) Str::uuid(), 'FEOperation' => 'updated', 'FETable' => 'MSTeam',
                    'FEException' => RuntimeException::class, 'FECode' => 'DEMO_WRITE_FAILURE',
                    'FEMessage' => 'Fictional example of a failed write. No actual failure was triggered by this seed.', 'FEIsExample' => true,
                ]);
                $error->FECB = $admin->getKey();
                $error->FEMB = $admin->getKey();
                $error->saveOrFail();
            });
        }
    }

    private function ensure(string $class, array $identity, array $values = []): FoundationModel
    {
        // Repeatable without resetting existing names, statuses, passwords, or history.
        return $class::query()->where($identity)->first() ?? $this->writer->create($class, array_merge($identity, $values), $this->actor);
    }

    private function person(Company $company, ?Team $team, string $worker, string $name, string $email, string $status, bool $assignedEmail = true): User
    {
        return $this->ensure(User::class, ['US_CONo' => $company->getKey(), 'USWorkerID' => $worker], [
            'US_TMNo' => $team?->getKey(), 'USName' => $name,
            'USContactEmail' => $email.'@personal.example', 'USEmail' => $assignedEmail ? $email.'@company.example' : null,
            'USRecoveryEmail' => $email.'@personal.example', 'USApprovedAt' => $assignedEmail ? now()->subDays(2) : null,
            'USStatus' => $status, 'USPassword' => in_array($status, ['active', 'deactivated']) ? Str::password(32) : null,
            'USActivatedAt' => in_array($status, ['active', 'deactivated']) ? now()->subDays(2) : null,
            'USDeactivatedAt' => $status === 'deactivated' ? now()->subDay() : null,
        ]);
    }

    private function assignRole(User $person, Role $role): void
    {
        $this->ensure(UserRole::class, ['UR_USNo' => $person->getKey(), 'UR_RLNo' => $role->getKey()]);
    }

    private function invite(Team $team, User $pic, ?User $person, string $worker, string $name, string $email, string $status): Invitation
    {
        return $this->ensure(Invitation::class, ['IV_TMNo' => $team->getKey(), 'IVWorkerID' => $worker], [
            'IVInviter_USNo' => $pic->getKey(), 'IV_USNo' => $person?->getKey(),
            'IVName' => $name, 'IVEmail' => $email.'@personal.example', 'IVStatus' => $status,
            'IVTokenHash' => null, 'IVExpiresAt' => $status === 'expired' ? now()->subDay() : now()->addDays(7),
            'IVAcceptedAt' => in_array($status, ['awaiting_review', 'approved', 'rejected']) ? now()->subDays(2) : null,
            'IVRevokedAt' => $status === 'revoked' ? now()->subDay() : null,
        ]);
    }

    private function mailbox(User $person, string $status, ?Invitation $invitation = null): void
    {
        $this->ensure(Mailbox::class, ['MX_USNo' => $person->getKey()], [
            'MX_IVNo' => $invitation?->getKey(), 'MXEmail' => $person->USEmail, 'MXStatus' => $status,
            'MXProvider' => 'mock', 'MXIsMock' => true, 'MXIdempotencyKey' => (string) Str::uuid(),
            'MXAttempts' => $status === 'not_started' ? 0 : 1,
            'MXFailureCode' => $status === 'failed' ? 'MOCK_TIMEOUT' : null,
            'MXReadyAt' => $status === 'ready' ? now()->subDay() : null,
        ]);
    }
}
