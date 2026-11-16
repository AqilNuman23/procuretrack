<?php

namespace Tests\Feature;

use App\Models\Foundation\AccessGrant;
use App\Models\Foundation\AccessGroup;
use App\Models\Foundation\Activation;
use App\Models\Foundation\Company;
use App\Models\Foundation\Department;
use App\Models\Foundation\EmailHistory;
use App\Models\Foundation\EmailVerification;
use App\Models\Foundation\FoundationLog;
use App\Models\Foundation\GroupMembership;
use App\Models\Foundation\Invitation;
use App\Models\Foundation\Module;
use App\Models\Foundation\PasswordReset;
use App\Models\Foundation\RecoveryRequest;
use App\Models\Foundation\Team;
use App\Models\Foundation\User;
use App\Notifications\AccountMail;
use App\Services\Access\AccessManager;
use App\Services\Access\ModuleAccess;
use App\Services\Access\ModuleCatalog;
use App\Services\AccountMailer;
use App\Services\OnboardingService;
use App\Services\PeopleAdministration;
use App\Services\RecoveryService;
use App\Support\Foundation\FoundationWriter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AccountAccessTest extends TestCase
{
    private static ?array $counters = null;

    private FoundationWriter $writer;

    private ModuleAccess $access;

    private AccessManager $manager;

    private const PASSWORD = 'A-private-test-password-123';

    protected function setUp(): void
    {
        parent::setUp();
        if (! app()->environment('testing') || DB::getDriverName() !== 'mysql' || DB::connection()->getDatabaseName() !== 'testing') {
            throw new LogicException('Account tests require the isolated MySQL database named testing.');
        }
        if (self::$counters === null) {
            $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
            self::$counters = DB::table('MSNumberSequence')->get()->map(fn ($row) => (array) $row)->all();
        } else {
            // No transaction wrapper: the write gateway must own the outer transaction.
            Schema::disableForeignKeyConstraints();
            try {
                foreach (array_keys(config('foundation.models')) as $table) {
                    DB::table($table)->truncate();
                }
            } finally {
                Schema::enableForeignKeyConstraints();
            }
            DB::table('MSNumberSequence')->insert(self::$counters);
        }
        Cache::flush();
        Notification::fake();
        $this->seed(DatabaseSeeder::class);
        $this->writer = app(FoundationWriter::class);
        $this->access = app(ModuleAccess::class);
        $this->manager = app(AccessManager::class);
        foreach (User::query()->whereIn('USWorkerID', ['PT-1000', 'PT-1011', 'PT-1025', 'PT-1038', 'PT-1043', 'PT-1042', 'PT-1029'])->get() as $user) {
            $this->writer->update($user, ['USPassword' => self::PASSWORD]);
        }
    }

    public function test_worker_login_logout_and_private_data_require_a_valid_session(): void
    {
        $this->get('/workspace')->assertRedirect('/login');
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->post('/login', ['login' => 'lina@company.example', 'password' => self::PASSWORD])->assertSessionHasErrors('login');
        $this->post('/login', ['login' => 'PT-1042', 'password' => self::PASSWORD])->assertSessionHasErrors('login');
        $this->post('/login', ['login' => 'PT-1029', 'password' => self::PASSWORD])->assertSessionHasErrors('login');
        $this->post('/login', ['login' => 'PT-1043', 'password' => self::PASSWORD])->assertRedirect('/workspace');
        $this->get('/workspace')->assertOk()->assertSee('Help Centre')->assertDontSee('Finance overview');
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('worker_id', 'PT-1043')->assertJsonMissingPath('USPassword');
        $this->get('/my-account')->assertOk()->assertSee('lina@personal.example');
        $this->post('/logout')->assertRedirect('/login');
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->get('/workspace')->assertRedirect('/login');
    }

    public function test_local_admin_credentials_are_blocked_outside_local_and_private_password_setup_disables_them(): void
    {
        // Switching the environment also switches Laravel's test-only CSRF bypass.
        // Real CSRF behaviour is checked separately over HTTP.
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $admin = $this->person('PT-1000');
        $this->writer->update($admin, ['USLoginName' => 'admin', 'USPassword' => 'admin', 'USLocalDemo' => true]);
        $this->app->detectEnvironment(fn () => 'local');
        $this->post('/login', ['login' => 'admin', 'password' => 'admin'])->assertRedirect('/workspace');
        $this->get('/access')->assertOk()->assertSee('Authorize a team PIC');
        $this->app->detectEnvironment(fn () => 'production');
        $this->get('/workspace')->assertRedirect('/login');
        $this->post('/login', ['login' => 'admin', 'password' => 'admin'])->assertSessionHasErrors('login');
        $this->app->detectEnvironment(fn () => 'testing');
        $this->artisan('foundation:admin-password', ['worker' => 'PT-1000'])
            ->expectsQuestion('Choose a private application password (at least 12 characters)', self::PASSWORD)
            ->expectsQuestion('Confirm password', self::PASSWORD)->assertExitCode(0);
        $this->assertFalse($admin->fresh()->USLocalDemo);
        $this->assertTrue(Hash::check(self::PASSWORD, $admin->fresh()->USPassword));
    }

    public function test_normal_reset_is_generic_single_use_and_invalidates_existing_sessions(): void
    {
        $worker = $this->person('PT-1043');
        $this->from('/forgot-password')->post('/forgot-password', ['worker_id' => 'NOT-FOUND', 'email' => 'unknown@personal.example'])
            ->assertRedirect('/forgot-password')->assertSessionHas('status', RecoveryService::GENERIC);
        $this->from('/forgot-password')->post('/forgot-password', ['worker_id' => $worker->USWorkerID, 'email' => $worker->USRecoveryEmail])
            ->assertRedirect('/forgot-password')->assertSessionHas('status', RecoveryService::GENERIC);
        Notification::assertSentOnDemand(AccountMail::class, fn ($mail, $channels, $to) => $mail->kind === 'reset' && $to->routes['mail'] === $worker->USRecoveryEmail);
        $token = $this->token('reset');
        $this->assertNotSame($token, PasswordReset::query()->sole()->PSTokenHash);
        $this->signIn('PT-1043');
        app(RecoveryService::class)->reset($token, 'A-new-private-password-456');
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->assertTrue(Hash::check('A-new-private-password-456', $worker->fresh()->USPassword));
        try {
            app(RecoveryService::class)->reset($token, 'Another-private-password-789');
            $this->fail('Used link accepted.');
        } catch (ValidationException) {
            $this->assertSame(1, $worker->fresh()->USAuthVersion);
        }
    }

    public function test_reissued_and_expired_reset_links_cannot_change_a_password(): void
    {
        $worker = $this->person('PT-1043');
        $recovery = app(RecoveryService::class);
        $recovery->requestReset($worker->USWorkerID, $worker->USRecoveryEmail);
        $first = $this->token('reset');
        $recovery->requestReset($worker->USWorkerID, $worker->USRecoveryEmail);
        $second = $this->token('reset');
        foreach ([$first, $second] as $index => $token) {
            if ($index === 1) {
                $this->travel(31)->minutes();
            }
            try {
                $recovery->reset($token, 'A-new-private-password-456');
                $this->fail('Invalid reset link accepted.');
            } catch (ValidationException) {
                $this->assertSame(0, $worker->fresh()->USAuthVersion);
            }
        }
    }

    public function test_assisted_recovery_requires_the_right_pic_and_verifies_new_email_before_replacing_it(): void
    {
        $worker = $this->person('PT-1043');
        $new = 'replacement.lina@personal.example';
        $this->post('/forgot-invitation-email', ['worker_id' => $worker->USWorkerID, 'new_email' => $new])->assertRedirect();
        $item = RecoveryRequest::query()->sole();
        Notification::assertSentOnDemand(AccountMail::class, fn ($mail, $channels, $to) => $mail->kind === 'review' && $to->routes['mail'] === 'farid@company.example');
        $this->signIn('PT-1025');
        $this->post(route('recovery.review', $item), ['decision' => 'approve', 'method' => 'in_person', 'verified' => 1, 'reason' => 'Confirmed employee identity in person.'])->assertForbidden();
        $this->signIn('PT-1011');
        $this->get('/recovery-requests')->assertOk()->assertSee($worker->USName);
        $this->get(route('recovery.show', $item))->assertOk();
        $this->post(route('recovery.review', $item), ['decision' => 'approve', 'method' => 'in_person', 'reason' => 'Confirmed employee identity in person.'])->assertSessionHasErrors('verified');
        $this->post(route('recovery.review', $item), ['decision' => 'approve', 'method' => 'in_person', 'verified' => 1, 'reason' => 'Confirmed employee identity in person.'])->assertRedirect('/recovery-requests');
        Notification::assertSentOnDemand(AccountMail::class, fn ($mail, $channels, $to) => $mail->kind === 'reset' && $to->routes['mail'] === $worker->USEmail);
        $this->assertSame('approved', $item->fresh()->RQStatus);
        $token = $this->token('reset');
        $this->post('/reset-password', ['token' => $token, 'password' => 'A-new-private-password-456', 'password_confirmation' => 'A-new-private-password-456', 'confirm_recovery_email' => 1])->assertRedirect('/login');
        $this->assertSame('lina@personal.example', $worker->fresh()->USRecoveryEmail);
        $this->assertSame(0, EmailHistory::query()->count());
        $verification = $this->token('verify');
        $this->get('/verify-recovery-email/'.$verification)->assertOk();
        $this->post('/verify-recovery-email', ['token' => $verification])->assertRedirect('/login');
        $worker->refresh();
        $this->assertSame($new, $worker->USRecoveryEmail);
        $this->assertSame('lina@personal.example', $worker->USContactEmail);
        $history = EmailHistory::query()->sole();
        $this->assertSame('lina@personal.example', $history->EHOldEmail);
        $this->assertSame($new, $history->EHNewEmail);
        $this->assertNotSame($new, DB::table('TREmailHistory')->value('EHNewEmail'));
        $this->assertStringNotContainsString($new, FoundationLog::query()->get()->toJson());
        $this->post('/verify-recovery-email', ['token' => $verification])->assertSessionHasErrors('token');
        $this->assertSame(1, EmailHistory::query()->count());
        $this->signIn('PT-1000');
        $this->get('/activity')->assertOk()->assertSee($new);
        $this->signIn('PT-1011');
        $this->get('/activity')->assertForbidden();
    }

    public function test_pic_recovery_goes_to_an_administrator_and_cannot_be_self_approved(): void
    {
        $pic = $this->person('PT-1011');
        app(RecoveryService::class)->requestAssistance($pic->USWorkerID, null);
        $item = RecoveryRequest::query()->sole();
        Notification::assertSentOnDemand(AccountMail::class, fn ($mail, $channels, $to) => $mail->kind === 'review' && $to->routes['mail'] === 'alex@company.example');
        $this->signIn('PT-1011');
        $this->post(route('recovery.review', $item), ['decision' => 'approve', 'method' => 'in_person', 'verified' => 1, 'reason' => 'Attempt to approve my own request.'])->assertForbidden();
        $this->signIn('PT-1000');
        $this->post(route('recovery.review', $item), ['decision' => 'approve', 'method' => 'trusted_contact', 'verified' => 1, 'reason' => 'Confirmed through established company contact.'])->assertRedirect('/recovery-requests');
    }

    public function test_pic_cannot_exceed_delegated_level_cross_teams_create_groups_or_grant_self_access(): void
    {
        $admin = $this->person('PT-1000');
        $pic = $this->person('PT-1011');
        $worker = $this->person('PT-1043');
        $root = $this->module('finance');
        $leaf = $this->module('finance.overview');
        $this->manager->delegation($admin, $pic, $root, 'read', true);
        $this->signIn('PT-1011');
        $this->post('/access/individual', ['users' => [$worker->USNo], 'module' => $leaf->MONo, 'level' => 'read'])->assertRedirect();
        $this->post('/access/individual', ['users' => [$worker->USNo], 'module' => $leaf->MONo, 'level' => 'edit'])->assertForbidden();
        $this->post('/access/individual', ['users' => [$pic->USNo], 'module' => $leaf->MONo, 'level' => 'read'])->assertForbidden();
        $this->post('/access/individual', ['users' => [$this->person('PT-1025')->USNo], 'module' => $leaf->MONo, 'level' => 'read'])->assertForbidden();
        $this->post('/access/individual', ['users' => [$worker->USNo], 'module' => $this->module('settings.company')->MONo, 'level' => 'read'])->assertForbidden();
        $this->post('/access/groups', ['name' => 'Escalation', 'entries' => [['module' => $leaf->MONo, 'level' => 'edit']]])->assertForbidden();
        $this->get(route('users.show', $this->person('PT-1025')))->assertForbidden();
        $this->signIn('PT-1043');
        $this->get('/modules/finance.overview')->assertOk()->assertSee('Read');
        $this->assertFalse(Gate::forUser($worker)->allows('edit', $leaf));
        $this->get('/access')->assertForbidden();
    }

    public function test_revoking_pic_authority_removes_only_derived_grants_and_does_not_restore_them_on_reenable(): void
    {
        $admin = $this->person('PT-1000');
        $pic = $this->person('PT-1011');
        $worker = $this->person('PT-1043');
        $root = $this->module('finance');
        $leaf = $this->module('finance.overview');
        $this->manager->delegation($admin, $pic, $root, 'edit', true);
        $this->manager->direct($pic, [$worker->USNo], $leaf, 'edit');
        $this->manager->direct($admin, [$worker->USNo], $leaf, 'read');
        $this->assertTrue($this->access->can($worker, $leaf, 'edit'));
        $this->manager->delegation($admin, $pic, $root, 'read', false);
        $this->assertTrue($this->access->can($worker, $leaf));
        $this->assertFalse($this->access->can($worker, $leaf, 'edit'));
        $adminGrant = AccessGrant::query()->where('AX_USNo', $worker->USNo)->whereNull('AX_DLNo')->sole();
        $this->manager->revoke($admin, $adminGrant);
        $this->signIn('PT-1043');
        $this->get('/modules/finance.overview')->assertForbidden();
        $this->manager->delegation($admin, $pic, $root, 'edit', true);
        $this->assertFalse($this->access->can($worker, $leaf));
    }

    public function test_group_assignment_is_atomic_and_read_only_cannot_call_the_edit_endpoint(): void
    {
        $admin = $this->person('PT-1000');
        $pic = $this->person('PT-1011');
        $worker = $this->person('PT-1043');
        $company = $this->module('settings.company');
        $finance = $this->module('finance.overview');
        $group = $this->manager->group($admin, 'Mixed scope', [['module' => $finance->MONo, 'level' => 'edit'], ['module' => $company->MONo, 'level' => 'read']]);
        $this->manager->delegation($admin, $pic, $this->module('finance'), 'edit', true);
        try {
            $this->manager->assignGroup($pic, [$worker->USNo], $group);
            $this->fail('Out-of-scope group assigned.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame(0, GroupMembership::query()->count());
        $this->assertSame(0, AccessGrant::query()->count());
        $this->manager->assignGroup($admin, [$worker->USNo], $group);
        $this->signIn('PT-1043');
        $this->get('/modules/settings.company')->assertOk();
        $this->post('/company', ['name' => 'Not permitted'])->assertForbidden();
        $this->manager->direct($admin, [$worker->USNo], $company, 'edit');
        $this->post('/company', ['name' => 'Authorized company edit'])->assertRedirect();
        $this->assertSame('Authorized company edit', $worker->company->fresh()->COName);
        $this->manager->deactivateGroup($admin, $group);
        $this->get('/modules/finance.overview')->assertForbidden();
        $this->get('/modules/settings.company')->assertOk();
    }

    public function test_deactivation_and_missing_mailbox_readiness_end_existing_sessions(): void
    {
        $worker = $this->person('PT-1043');
        $this->signIn('PT-1043');
        $this->writer->update($worker, ['USStatus' => 'deactivated']);
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->writer->update($worker->fresh(), ['USStatus' => 'active']);
        $this->signIn('PT-1043');
        $this->writer->update($worker->mailbox, ['MXStatus' => 'failed']);
        $this->get('/workspace')->assertRedirect('/login');
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['login' => 'PT-1043', 'password' => 'wrong-password'])->assertSessionHasErrors('login');
        }
        $this->post('/login', ['login' => 'PT-1043', 'password' => 'wrong-password'])->assertStatus(429);
    }

    public function test_a_replacement_address_requires_employee_consent_even_after_pic_approval(): void
    {
        $worker = $this->person('PT-1043');
        $service = app(RecoveryService::class);
        $service->requestAssistance($worker->USWorkerID, 'unrecognized@personal.example');
        $service->review($this->person('PT-1011'), RecoveryRequest::query()->sole(), 'approve', 'in_person', 'Independently verified the employee.');
        $token = $this->token('reset');
        $this->get('/reset-password/'.$token)->assertOk()->assertSee('unrecognized@personal.example')->assertSee('confirm_recovery_email');
        $service->reset($token, 'A-new-private-password-456');
        $this->assertSame('lina@personal.example', $worker->fresh()->USRecoveryEmail);
        $this->assertSame(0, EmailVerification::query()->count());
        $this->assertSame(0, EmailHistory::query()->count());
        $this->assertSame(0, Notification::sent(new AnonymousNotifiable, AccountMail::class, fn ($mail) => $mail->kind === 'verify')->count());
    }

    public function test_duplicate_and_stale_assistance_requests_do_not_authorize_recovery(): void
    {
        $worker = $this->person('PT-1043');
        $service = app(RecoveryService::class);
        $service->requestAssistance($worker->USWorkerID, null);
        $service->requestAssistance($worker->USWorkerID, 'different@personal.example');
        $this->assertSame(1, RecoveryRequest::query()->count());
        $item = RecoveryRequest::query()->sole();
        $service->requestReset($worker->USWorkerID, $worker->USRecoveryEmail);
        $service->reset($this->token('reset'), 'A-new-private-password-456');
        try {
            $service->review($this->person('PT-1011'), $item, 'approve', 'in_person', 'Verified through an established contact.');
            $this->fail('Stale request approved.');
        } catch (ValidationException) {
            $this->assertSame('pending', $item->fresh()->RQStatus);
        }
        $service->requestAssistance($worker->USWorkerID, null);
        $this->assertSame(2, RecoveryRequest::query()->count());
    }

    public function test_group_delegation_caps_and_membership_revocation_preserve_independent_access(): void
    {
        $admin = $this->person('PT-1000');
        $pic = $this->person('PT-1011');
        $worker = $this->person('PT-1043');
        $root = $this->module('finance');
        $leaf = $this->module('finance.overview');
        $group = AccessGroup::query()->where('AGName', 'Finance editors')->firstOrFail();
        $this->manager->delegation($admin, $pic, $root, 'edit', true);
        $this->manager->assignGroup($pic, [$worker->USNo], $group);
        $this->assertTrue($this->access->can($worker, $leaf, 'edit'));
        $this->manager->delegation($admin, $pic, $root, 'read', true);
        $this->assertTrue($this->access->can($worker, $leaf));
        $this->assertFalse($this->access->can($worker, $leaf, 'edit'));
        $this->manager->delegation($admin, $pic, $root, 'edit', true);
        $this->assertFalse($this->access->can($worker, $leaf, 'edit'));
        $this->manager->direct($admin, [$worker->USNo], $leaf, 'edit');
        $this->manager->revokeMembership($pic, GroupMembership::query()->sole());
        $this->assertTrue($this->access->can($worker, $leaf, 'edit'));
        $this->assertFalse(GroupMembership::query()->sole()->GMActive);
        $this->signIn('PT-1011');
        $this->get('/access')->assertOk();
    }

    public function test_other_company_records_and_administrative_grants_cannot_be_forged(): void
    {
        $admin = $this->person('PT-1000');
        $other = $this->writer->create(Company::class, ['COCode' => 'OTHER', 'COName' => 'Other company']);
        $outsider = $this->writer->create(User::class, ['US_CONo' => $other->CONo, 'USWorkerID' => 'OTHER-01', 'USName' => 'Other company person', 'USContactEmail' => 'outsider@personal.example']);
        $this->signIn('PT-1000');
        $this->get(route('users.show', $outsider))->assertForbidden();
        $this->post('/access/individual', ['users' => [$outsider->USNo], 'module' => $this->module('finance.overview')->MONo, 'level' => 'edit'])->assertForbidden();
        $this->post('/access/individual', ['users' => [$this->person('PT-1043')->USNo], 'module' => $this->module('settings.access')->MONo, 'level' => 'edit'])->assertStatus(422);
        $this->assertSame(0, AccessGrant::query()->count());
    }

    public function test_sanctum_session_endpoint_and_malformed_input_are_handled_safely(): void
    {
        $this->postJson('/login', ['login' => ['unexpected'], 'password' => self::PASSWORD])->assertUnprocessable();
        $this->signIn('PT-1043');
        $this->getJson('/api/user', ['Origin' => config('app.url')])->assertOk()->assertJsonPath('worker_id', 'PT-1043')->assertJsonMissingPath('USRecoveryEmail');
        $this->writer->update($this->person('PT-1043'), ['USAuthVersion' => 1]);
        $this->getJson('/api/user', ['Origin' => config('app.url')])->assertUnauthorized();
    }

    public function test_recovery_links_are_not_sent_to_a_log_mail_transport(): void
    {
        config(['mail.default' => 'log']);
        $this->app->detectEnvironment(fn () => 'local');
        try {
            $worker = $this->person('PT-1043');
            app(RecoveryService::class)->requestReset($worker->USWorkerID, $worker->USRecoveryEmail);
            $this->assertNotNull(PasswordReset::query()->sole()->PSRevokedAt);
            Notification::assertNothingSent();
        } finally {
            $this->app->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_first_administrator_bootstrap_uses_a_private_password_and_requires_verified_existing_mailboxes(): void
    {
        config(['foundation.company_code' => 'NEW']);
        $options = ['worker' => 'NEW-ADMIN', '--name' => 'Initial Administrator', '--email' => 'admin@new-company.example',
            '--recovery-email' => 'admin@personal.example', '--company-name' => 'New company'];
        $this->artisan('foundation:bootstrap-admin', $options)->assertExitCode(1);
        $this->artisan('foundation:bootstrap-admin', $options + ['--mailbox-verified' => true])
            ->expectsQuestion('Choose a private application password (at least 12 characters)', self::PASSWORD)
            ->expectsQuestion('Confirm password', self::PASSWORD)->assertExitCode(0);
        $admin = app(RecoveryService::class)->lookup('NEW-ADMIN');
        $this->assertNotNull($admin);
        $this->assertTrue($this->access->administrator($admin));
        $this->assertTrue($this->access->eligible($admin));
        $this->assertFalse($admin->USLocalDemo);
        $this->assertFalse($admin->mailbox->MXIsMock);
        $this->assertTrue(Hash::check(self::PASSWORD, $admin->USPassword));
        $this->post('/login', ['login' => 'admin', 'password' => self::PASSWORD])->assertRedirect('/workspace');
        $this->artisan('foundation:bootstrap-admin', $options + ['--mailbox-verified' => true])->assertExitCode(1);
    }

    public function test_module_catalog_sync_renames_once_without_expanding_employee_grants(): void
    {
        $before = AccessGrant::query()->count();
        config(['modules.finance.name' => 'Accounts']);
        app(ModuleCatalog::class)->sync($this->person('PT-1000'));
        $this->assertSame('Accounts', $this->module('finance')->MOName);
        $history = FoundationLog::query()->count();
        app(ModuleCatalog::class)->sync();
        $this->assertSame($history, FoundationLog::query()->count());
        $this->assertSame($before, AccessGrant::query()->count());
        $unknown = $this->writer->create(Module::class, ['MOCode' => 'unregistered', 'MOName' => 'Unregistered']);
        $this->assertFalse($this->access->can($this->person('PT-1000'), $unknown));
    }

    public function test_password_change_checks_the_current_password_and_invalidates_sessions(): void
    {
        $worker = $this->person('PT-1043');
        $this->signIn('PT-1043');
        $this->post('/my-account/password', ['current_password' => 'wrong', 'password' => 'A-new-private-password-456', 'password_confirmation' => 'A-new-private-password-456'])
            ->assertSessionHasErrors('current_password');
        $oversized = str_repeat('界', 25);
        $this->post('/my-account/password', ['current_password' => self::PASSWORD, 'password' => $oversized, 'password_confirmation' => $oversized])
            ->assertSessionHasErrors('password');
        $this->assertSame(0, $worker->fresh()->USAuthVersion);
        $this->post('/my-account/password', ['current_password' => self::PASSWORD, 'password' => 'A-new-private-password-456', 'password_confirmation' => 'A-new-private-password-456'])
            ->assertRedirect('/login');
        $this->assertSame(1, $worker->fresh()->USAuthVersion);
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->post('/login', ['login' => 'PT-1043', 'password' => 'A-new-private-password-456'])->assertRedirect('/workspace');
    }

    public function test_team_administration_requires_admin_and_pic_assignment_revokes_old_authority(): void
    {
        $admin = $this->person('PT-1000');
        $pic = $this->person('PT-1011');
        $member = $this->person('PT-1043');
        $team = $pic->team;
        $this->manager->delegation($admin, $pic, $this->module('finance'), 'edit', true);
        $this->manager->direct($pic, [$member->USNo], $this->module('finance.overview'), 'edit');
        $this->signIn('PT-1043');
        $this->post('/teams', ['code' => 'DENIED', 'name' => 'Denied'])->assertForbidden();
        $this->get('/teams')->assertForbidden();
        $this->signIn('PT-1000');
        $this->get('/teams')->assertOk()->assertSee($team->TMName);
        $this->get(route('teams.show', $team))->assertOk();
        $this->post('/teams', ['code' => 'NEW', 'name' => 'New team'])->assertRedirect();
        $this->post('/teams', ['code' => 'NEW', 'name' => 'Duplicate'])->assertSessionHasErrors('code');
        $this->post(route('teams.pic', $team), ['pic' => $this->person('PT-1025')->USNo])->assertSessionHasErrors('pic');
        $this->post(route('teams.pic', $team), ['pic' => $member->USNo])->assertRedirect();
        $this->assertTrue($this->access->pic($member->fresh()));
        $this->assertFalse($this->access->pic($pic->fresh()));
        $this->assertFalse($this->access->can($member->fresh(), 'finance.overview'));
        $this->assertSame(0, $pic->delegations()->where('DLActive', true)->count());
        $this->post(route('teams.pic', $team), ['pic' => $pic->USNo])->assertRedirect();
        $this->assertFalse($this->access->can($member->fresh(), 'finance.overview'));
    }

    public function test_administration_protects_last_admin_current_pic_and_reactivation_readiness(): void
    {
        $this->signIn('PT-1000');
        $admin = $this->person('PT-1000');
        $this->post(route('users.update', $admin), ['name' => $admin->USName, 'team' => $admin->US_TMNo, 'status' => 'deactivated'])->assertSessionHasErrors('status');
        $pic = $this->person('PT-1011');
        $this->post(route('users.update', $pic), ['name' => $pic->USName, 'team' => $pic->US_TMNo, 'status' => 'deactivated'])->assertSessionHasErrors('team');
        $member = $this->person('PT-1043');
        $this->post(route('users.update', $member), ['name' => 'Updated member', 'team' => $member->US_TMNo, 'status' => 'deactivated'])->assertRedirect();
        $this->assertSame('deactivated', $member->fresh()->USStatus);
        $this->assertSame(1, $member->fresh()->USAuthVersion);
        $this->post(route('users.update', $member), ['name' => $member->USName, 'team' => $member->US_TMNo, 'status' => 'active'])->assertRedirect();
        $this->assertSame('active', $member->fresh()->USStatus);
        $this->writer->update($member->mailbox, ['MXStatus' => 'failed']);
        $this->post(route('users.update', $member), ['name' => $member->USName, 'team' => $member->US_TMNo, 'status' => 'active'])->assertSessionHasErrors('status');
        $other = $this->writer->create(Company::class, ['COCode' => 'OTHER', 'COName' => 'Other company']);
        $team = $this->writer->create(Team::class, ['TM_CONo' => $other->CONo, 'TMCode' => 'OTHER', 'TMName' => 'Other team']);
        $this->post(route('users.update', $member), ['name' => $member->USName, 'team' => $team->TMNo, 'status' => 'deactivated'])->assertSessionHasErrors('team');
    }

    public function test_onboarding_runs_from_invitation_to_private_password_and_preserves_separate_states(): void
    {
        $pic = $this->person('PT-1011');
        $service = app(OnboardingService::class);
        $this->signIn('PT-1011');
        $this->get('/invitations')->assertOk();
        $this->post('/invitations', ['team' => $pic->US_TMNo, 'name' => 'New Member', 'worker_id' => 'PT-NEW', 'email' => 'new@personal.example'])->assertRedirect();
        $invitation = Invitation::query()->where('IVWorkerID', 'PT-NEW')->sole();
        $token = $this->token('invitation');
        $this->get('/invitation/'.$token)->assertOk();
        $this->post('/invitation', ['token' => $token])->assertRedirect('/login');
        $this->assertSame('awaiting_review', $invitation->fresh()->IVStatus);
        $this->post('/invitation', ['token' => $token])->assertSessionHasErrors('token');
        $this->post(route('invitations.action', $invitation), ['action' => 'approved'])->assertForbidden();
        $this->signIn('PT-1000');
        $this->post(route('invitations.action', $invitation), ['action' => 'approved'])->assertRedirect();
        $user = $invitation->fresh()->user;
        $this->assertSame('pending_activation', $user->USStatus);
        $this->assertFalse($this->access->eligible($user));
        $this->assertSame('pending', $user->mailbox->MXStatus);
        $this->get('/mailboxes')->assertOk()->assertSee('New Member');
        $this->post(route('mailboxes.update', $user), ['action' => 'verify', 'email' => 'new@company.example'])->assertSessionHasErrors('verified');
        $this->post(route('mailboxes.update', $user), ['action' => 'verify', 'email' => 'new@company.example', 'verified' => 1])->assertRedirect();
        $this->assertFalse($user->fresh()->mailbox->MXIsMock);
        $this->assertFalse($this->access->eligible($user->fresh()));
        Notification::assertSentOnDemand(AccountMail::class, fn ($mail, $channels, $to) => $mail->kind === 'activation' && $to->routes['mail'] === 'new@personal.example');
        $activation = $this->token('activation');
        $this->get('/activation/'.$activation)->assertOk();
        $this->post('/activation', ['token' => $activation, 'worker_id' => 'WRONG', 'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD])->assertSessionHasErrors('token');
        $this->post('/activation', ['token' => $activation, 'worker_id' => 'PT-NEW', 'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD])->assertRedirect('/login');
        $this->assertTrue($this->access->eligible($user->fresh()));
        $this->assertTrue(Hash::check(self::PASSWORD, $user->fresh()->USPassword));
        $this->post('/activation', ['token' => $activation, 'worker_id' => 'PT-NEW', 'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD])->assertSessionHasErrors('token');
    }

    public function test_invitation_resend_expiry_rejection_and_cross_team_boundaries(): void
    {
        $pic = $this->person('PT-1011');
        $this->signIn('PT-1011');
        $data = ['team' => $pic->US_TMNo, 'name' => 'New Member', 'worker_id' => 'PT-NEW', 'email' => 'new@personal.example'];
        $this->post('/invitations', $data)->assertRedirect();
        $invitation = Invitation::query()->where('IVWorkerID', 'PT-NEW')->sole();
        $old = $this->token('invitation');
        $this->post('/invitations', $data)->assertSessionHasErrors('worker_id');
        $this->post('/invitations', array_replace($data, ['team' => $this->person('PT-1025')->US_TMNo, 'worker_id' => 'PT-OTHER', 'email' => 'other@personal.example']))->assertForbidden();
        $this->post(route('invitations.action', $invitation), ['action' => 'resend'])->assertRedirect();
        $new = $this->token('invitation');
        $this->assertNotSame($old, $new);
        $this->post('/invitation', ['token' => $old])->assertSessionHasErrors('token');
        $this->writer->update($invitation->fresh(), ['IVExpiresAt' => now()->subMinute()]);
        $this->post('/invitation', ['token' => $new])->assertSessionHasErrors('token');
        $this->post(route('invitations.action', $invitation), ['action' => 'resend'])->assertRedirect();
        $this->post('/invitation', ['token' => $this->token('invitation')])->assertRedirect();
        $this->signIn('PT-1000');
        $this->post(route('invitations.action', $invitation), ['action' => 'rejected'])->assertSessionHasErrors('reason');
        $this->post(route('invitations.action', $invitation), ['action' => 'rejected', 'reason' => 'Details could not be verified.'])->assertRedirect();
        $this->post(route('invitations.action', $invitation), ['action' => 'approved'])->assertSessionHasErrors('invitation');
        $this->assertSame(0, User::query()->where('USWorkerID', 'PT-NEW')->count());
    }

    public function test_department_reference_module_scopes_records_validates_and_audits_changes(): void
    {
        $this->signIn('PT-1043');
        $this->get('/departments')->assertForbidden();
        $this->signIn('PT-1000');
        $this->get('/departments')->assertOk();
        $this->post('/departments', ['code' => 'NEW', 'name' => 'New Department', 'active' => 1])->assertRedirect();
        $record = Department::query()->where('DPCode', 'NEW')->sole();
        $this->get(route('departments.show', $record))->assertOk()->assertSee('New Department');
        $this->post('/departments', ['code' => 'NEW', 'name' => 'Duplicate', 'active' => 1])->assertSessionHasErrors('code');
        $this->post(route('departments.update', $record), ['code' => 'NEW', 'name' => 'Revised Department', 'active' => 0])->assertRedirect();
        $this->get('/departments?search=Revised&status=inactive&sort=code&direction=desc')->assertOk()->assertSee('Revised Department');
        $this->assertSame(2, FoundationLog::query()->where('FLTable', 'MSDepartment')->where('FLRecordNo', $record->DPNo)->count());
        $other = $this->writer->create(Company::class, ['COCode' => 'OTHER', 'COName' => 'Other company']);
        $outsider = $this->writer->create(Department::class, ['DP_CONo' => $other->CONo, 'DPCode' => 'PRIVATE', 'DPName' => 'Private Department']);
        $this->get('/departments')->assertDontSee('Private Department');
        $this->get(route('departments.show', $outsider))->assertNotFound();
        $this->manager->direct($this->person('PT-1000'), [$this->person('PT-1043')->USNo], $this->module('settings.departments'), 'read');
        $this->signIn('PT-1043');
        $this->get('/departments')->assertOk();
        $this->post('/departments', ['code' => 'DENIED', 'name' => 'Denied', 'active' => 1])->assertForbidden();
    }

    public function test_transfer_revokes_derived_grants_and_old_sessions_without_removing_independent_access(): void
    {
        $admin = $this->person('PT-1000');
        $pic = $this->person('PT-1011');
        $member = $this->person('PT-1043');
        $leaf = $this->module('finance.overview');
        $this->manager->delegation($admin, $pic, $this->module('finance'), 'edit', true);
        $this->manager->direct($pic, [$member->USNo], $leaf, 'edit');
        $this->manager->direct($admin, [$member->USNo], $leaf, 'read');
        $this->signIn('PT-1043');
        app(PeopleAdministration::class)->updateUser($admin, $member, ['name' => $member->USName, 'team' => $this->person('PT-1025')->US_TMNo, 'status' => 'active']);
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->assertTrue($this->access->can($member->fresh(), $leaf));
        $this->assertFalse($this->access->can($member->fresh(), $leaf, 'edit'));
        app(PeopleAdministration::class)->updateUser($admin, $member->fresh(), ['name' => $member->USName, 'team' => $pic->US_TMNo, 'status' => 'active']);
        $this->assertFalse($this->access->can($member->fresh(), $leaf, 'edit'));
    }

    public function test_onboarding_failed_delivery_revokes_tokens_and_allows_explicit_retry(): void
    {
        $pic = $this->person('PT-1011');
        $this->mock(AccountMailer::class, function ($mock) {
            $mock->shouldReceive('url')->andReturn('https://example.test/invitation/token');
            $mock->shouldReceive('send')->andReturn(false);
        });
        $this->signIn('PT-1011');
        $this->post('/invitations', ['team' => $pic->US_TMNo, 'name' => 'Delivery failure', 'worker_id' => 'PT-FAIL', 'email' => 'fail@personal.example'])->assertSessionHasErrors('invitation');
        $invitation = Invitation::query()->where('IVWorkerID', 'PT-FAIL')->sole();
        $this->assertNull($invitation->IVTokenHash);
        $this->assertSame('invited', $invitation->IVStatus);
    }

    public function test_activation_resend_revokes_old_links_expiry_is_enforced_and_components_render(): void
    {
        $pic = $this->person('PT-1011');
        $admin = $this->person('PT-1000');
        $service = app(OnboardingService::class);
        $invitation = $service->invite($pic, ['team' => $pic->US_TMNo, 'name' => 'Activation test', 'worker_id' => 'PT-ACT', 'email' => 'activate@personal.example']);
        $service->accept($this->token('invitation'));
        $service->review($admin, $invitation, 'approved', null);
        $user = $invitation->fresh()->user;
        $service->mailbox($admin, $user, ['email' => 'activate@company.example', 'verified' => 1]);
        $old = $this->token('activation');
        $service->sendActivation($admin, $user);
        $current = $this->token('activation');
        $this->post('/activation', ['token' => $old, 'worker_id' => 'PT-ACT', 'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD])->assertSessionHasErrors('token');
        $activation = Activation::query()->where('AVTokenHash', hash('sha256', $current))->sole();
        $this->writer->update($activation, ['AVExpiresAt' => now()->subMinute()]);
        $this->post('/activation', ['token' => $current, 'worker_id' => 'PT-ACT', 'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD])->assertSessionHasErrors('token');
        $this->assertSame('pending_activation', $user->fresh()->USStatus);
        $this->signIn('PT-1043');
        $this->get('/components')->assertForbidden();
        $this->signIn('PT-1000');
        $this->get('/components')->assertOk()->assertSee('Loading records')->assertSee('Records could not be loaded');
    }

    private function person(string $worker): User
    {
        return User::query()->where('USWorkerID', $worker)->firstOrFail();
    }

    private function module(string $code): Module
    {
        return Module::query()->where('MOCode', $code)->firstOrFail();
    }

    private function signIn(string $worker): void
    {
        $user = $this->person($worker);
        $this->actingAs($user, 'web')->withSession(['auth_version' => $user->USAuthVersion]);
    }

    private function token(string $kind): string
    {
        $mail = Notification::sent(new AnonymousNotifiable, AccountMail::class, fn ($mail) => $mail->kind === $kind)->last();
        $this->assertNotNull($mail);

        return basename(parse_url($mail->url, PHP_URL_PATH));
    }
}
