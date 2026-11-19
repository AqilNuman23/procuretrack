<?php

namespace Database\Seeders;

use App\Models\Foundation\AccessGrant;
use App\Models\Foundation\Mailbox;
use App\Models\Foundation\Module;
use App\Models\Foundation\Role;
use App\Models\Foundation\Team;
use App\Models\Foundation\User;
use App\Models\Foundation\UserRole;
use App\Rules\ApplicationPassword;
use App\Services\Access\AccessManager;
use App\Services\Access\ModuleAccess;
use App\Services\Access\ModuleCatalog;
use App\Support\Foundation\FoundationChanges;
use App\Support\Foundation\FoundationWriter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class ProcurementDemoSeeder extends Seeder
{
    public function run(FoundationWriter $writer, AccessManager $manager, ModuleAccess $access, ModuleCatalog $catalog): void
    {
        if (! app()->environment('local') || config('foundation.company_code') !== 'DEMO') {
            throw new RuntimeException('Procurement demo users require the local DEMO company.');
        }

        $password = config('foundation.procurement_demo_password');
        if (Validator::make(['password' => $password], ['password' => ['required', 'string', new ApplicationPassword]])->fails()) {
            throw new RuntimeException('Set PROCUREMENT_DEMO_PASSWORD in your local .env to a password of at least 12 characters and at most 72 bytes.');
        }

        $admin = User::query()->where('USWorkerID', 'PT-1000')
            ->whereHas('company', fn ($query) => $query->where('COCode', 'DEMO'))->firstOrFail();
        if (! $access->administrator($admin) || ! $access->eligible($admin)) {
            throw new RuntimeException('Set up the active local DEMO administrator before adding procurement users.');
        }

        $catalog->sync($admin);
        $modules = Module::query()->where('MOActive', true)->get()->keyBy('MOCode');
        $teams = Team::query()->where('TM_CONo', $admin->US_CONo)->get()->keyBy('TMCode');
        $member = Role::query()->where('RLCode', 'member')->firstOrFail();
        $supplierAccess = ['edit' => ['suppliers.quotations', 'suppliers.deliveries', 'suppliers.claims'], 'read' => ['suppliers.orders']];
        $approvalAccess = ['edit' => ['procurement.approvals'], 'read' => ['reporting.notifications']];
        $profiles = [
            'PT-3101' => ['Requester', 'OPS', ['edit' => ['procurement.requests'], 'read' => ['reporting.notifications']]],
            'PT-3102' => ['Approver', 'OPS', $approvalAccess],
            'PT-3103' => ['Second approver', 'FIN', $approvalAccess],
            'PT-3104' => ['Procurement officer', 'OPS', [
                'edit' => ['procurement.quotations', 'procurement.orders', 'procurement.imports', 'suppliers.directory'],
                'read' => ['procurement.requests', 'procurement.deliveries', 'finance.budgets', 'reporting.notifications'],
            ]],
            'PT-3105' => ['Supplier A', null, $supplierAccess],
            'PT-3106' => ['Supplier B', null, $supplierAccess],
            'PT-3107' => ['Receiving officer', 'OPS', [
                'edit' => ['procurement.inspections'],
                'read' => ['procurement.orders', 'procurement.deliveries', 'reporting.notifications'],
            ]],
            'PT-3108' => ['Finance officer', 'FIN', [
                'edit' => ['finance.budgets', 'finance.claims', 'finance.certificates', 'finance.payments', 'reporting.exports'],
                'read' => ['finance.overview', 'procurement.orders', 'procurement.deliveries', 'reporting.notifications'],
            ]],
            'PT-3109' => ['Manager', 'OPS', [
                'read' => ['reporting.dashboard', 'finance.overview', 'finance.budgets', 'finance.claims', 'procurement.approvals', 'procurement.deliveries'],
            ]],
            'PT-3110' => ['Integration developer', 'TECH', ['read' => ['integrations.docs']]],
        ];

        foreach ($profiles as $worker => [$name, $teamCode, $grants]) {
            $email = strtolower($worker).'@procurement.example';
            $user = User::query()->where('US_CONo', $admin->US_CONo)->where('USWorkerID', $worker)->first();
            if ($user && (! $user->USLocalDemo || $user->USContactEmail !== $email)) {
                throw new RuntimeException("Worker ID {$worker} already belongs to another account.");
            }
            $user ??= $writer->transaction($admin, function (FoundationChanges $changes) use ($admin, $teams, $teamCode, $worker, $name, $email, $member, $password) {
                $user = $changes->create(User::class, [
                    'US_CONo' => $admin->US_CONo,
                    'US_TMNo' => $teamCode === null ? null : $teams[$teamCode]->getKey(),
                    'USWorkerID' => $worker,
                    'USName' => 'Demo '.$name,
                    'USContactEmail' => $email,
                    'USRecoveryEmail' => $email,
                    'USEmail' => $email,
                    'USStatus' => 'active',
                    'USPassword' => $password,
                    'USLocalDemo' => true,
                    'USApprovedAt' => now(),
                    'USActivatedAt' => now(),
                ]);
                $changes->create(UserRole::class, ['UR_USNo' => $user->getKey(), 'UR_RLNo' => $member->getKey()]);
                $changes->create(Mailbox::class, [
                    'MX_USNo' => $user->getKey(),
                    'MXEmail' => $email,
                    'MXStatus' => 'ready',
                    'MXProvider' => 'mock',
                    'MXIsMock' => true,
                    'MXIdempotencyKey' => (string) Str::uuid(),
                    'MXReadyAt' => now(),
                ]);

                return $user;
            });

            foreach ($grants as $level => $codes) {
                foreach ($codes as $code) {
                    $module = $modules[$code];
                    $exists = AccessGrant::query()->where('AX_USNo', $user->getKey())
                        ->where('AX_MONo', $module->getKey())->where('AXGrantor_USNo', $admin->getKey())
                        ->whereNull('AX_GMNo')->exists();
                    if (! $exists) {
                        $manager->direct($admin, [$user->getKey()], $module, $level);
                    }
                }
            }
        }
    }
}
