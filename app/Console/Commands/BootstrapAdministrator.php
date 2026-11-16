<?php

namespace App\Console\Commands;

use App\Models\Foundation\Company;
use App\Models\Foundation\Mailbox;
use App\Models\Foundation\Role;
use App\Models\Foundation\User;
use App\Models\Foundation\UserRole;
use App\Rules\ApplicationPassword;
use App\Services\Access\ModuleCatalog;
use App\Support\Foundation\FoundationChanges;
use App\Support\Foundation\FoundationWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BootstrapAdministrator extends Command
{
    protected $signature = 'foundation:bootstrap-admin {worker} {--name=} {--email=} {--recovery-email=} {--company-name=} {--mailbox-verified}';

    protected $description = 'Create the first administrator with a private password and an externally verified existing mailbox';

    public function handle(FoundationWriter $writer, ModuleCatalog $catalog): int
    {
        $data = ['worker' => strtoupper(trim($this->argument('worker'))), 'name' => $this->option('name'), 'email' => $this->option('email'),
            'recovery' => $this->option('recovery-email'), 'company' => $this->option('company-name')];
        $validator = Validator::make($data, ['worker' => ['required', 'string', 'max:50'], 'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:254'], 'recovery' => ['required', 'email', 'max:254'], 'company' => ['nullable', 'string', 'max:150']]);
        if ($validator->fails() || ! $this->option('mailbox-verified')) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            $this->error('Verify control of both email addresses outside the app first. --mailbox-verified records that confirmation; this command does not provision a mailbox.');

            return self::FAILURE;
        }
        $company = Company::query()->where('COCode', config('foundation.company_code'))->first();
        if ((! $company && ! $data['company']) || User::query()->where('USLoginName', 'admin')->exists()
            || ($company && $company->users()->whereHas('roleAssignments.role', fn ($q) => $q->where('RLCode', 'administrator'))->exists())) {
            $this->error('Provide a company name for initial setup. If an administrator already exists, use the controlled password command instead of bootstrapping another one.');

            return self::FAILURE;
        }
        $password = $this->secret('Choose a private application password (at least 12 characters)');
        $confirmation = $this->secret('Confirm password');
        if ($password !== $confirmation || Validator::make(['password' => $password], ['password' => ['required', 'string', new ApplicationPassword]])->fails()) {
            $this->error('Passwords must match, contain at least 12 characters, and fit within the 72-byte password limit.');

            return self::FAILURE;
        }
        $admin = $writer->transaction(null, function (FoundationChanges $changes) use ($company, $data, $password) {
            $company ??= $changes->create(Company::class, ['COCode' => config('foundation.company_code'), 'COName' => $data['company']]);
            $admin = $changes->create(User::class, [
                'US_CONo' => $company->getKey(), 'USWorkerID' => $data['worker'], 'USName' => $data['name'],
                'USContactEmail' => strtolower($data['recovery']), 'USRecoveryEmail' => strtolower($data['recovery']), 'USEmail' => strtolower($data['email']),
                'USLoginName' => 'admin', 'USPassword' => $password, 'USLocalDemo' => false, 'USStatus' => 'active', 'USApprovedAt' => now(), 'USActivatedAt' => now(),
            ]);
            $role = Role::query()->where('RLCode', 'administrator')->first() ?? $changes->create(Role::class, ['RLCode' => 'administrator', 'RLName' => 'Administrator']);
            $changes->create(UserRole::class, ['UR_USNo' => $admin->getKey(), 'UR_RLNo' => $role->getKey()]);
            $changes->create(Mailbox::class, ['MX_USNo' => $admin->getKey(), 'MXEmail' => $admin->USEmail, 'MXProvider' => 'manual-existing', 'MXIsMock' => false,
                'MXStatus' => 'ready', 'MXIdempotencyKey' => (string) Str::uuid(), 'MXReadyAt' => now()]);

            return $admin;
        });
        $catalog->sync($admin);
        $this->info('Administrator created with a private password. The existing mailbox was recorded as manually verified; no mailbox was provisioned.');

        return self::SUCCESS;
    }
}
