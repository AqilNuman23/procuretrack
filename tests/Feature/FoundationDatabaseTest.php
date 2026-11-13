<?php

namespace Tests\Feature;

use App\Models\Foundation\Company;
use App\Models\Foundation\FoundationError;
use App\Models\Foundation\FoundationLog;
use App\Models\Foundation\Invitation;
use App\Models\Foundation\Team;
use App\Models\Foundation\TeamPic;
use App\Models\Foundation\User;
use App\Support\Foundation\FoundationChanges;
use App\Support\Foundation\FoundationWriter;
use Database\Seeders\FoundationSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class FoundationDatabaseTest extends TestCase
{
    private FoundationWriter $writer;

    protected function setUp(): void
    {
        parent::setUp();
        // These MySQL integration tests must never refresh the developer's database.
        if (! app()->environment('testing') || DB::getDriverName() !== 'mysql' || DB::connection()->getDatabaseName() !== 'testing') {
            throw new LogicException('Foundation tests require the isolated MySQL database named testing.');
        }
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
        $this->writer = app(FoundationWriter::class);
    }

    public function test_seeds_are_repeatable_and_relationships_use_no_keys(): void
    {
        $this->seed(FoundationSeeder::class);
        $company = Company::query()->firstOrFail();
        $pic = TeamPic::query()->with(['user', 'team'])->firstOrFail();
        $this->assertMatchesRegularExpression('/^CO\d{8}$/', $company->getKey());
        $this->assertSame('CONo', $company->getKeyName());
        $this->assertGreaterThan(0, $company->COID);
        $this->assertSame($pic->TP_TMNo, $pic->user->team->getKey());
        $this->assertSame($pic->TP_USNo, $pic->user->getKey());
        $this->assertSame($company->getKey(), FoundationLog::query()->where('FLTable', 'MSCompany')->firstOrFail()->subject->getKey());
        $this->assertNotNull(Team::query()->firstOrFail()->createdBy);

        $this->writer->update($company, ['COName' => 'Keep this edited name']);
        $before = $this->counts();
        $this->seed(FoundationSeeder::class);
        $this->assertSame($before, $this->counts());
        $this->assertSame('Keep this edited name', $company->fresh()->COName);
    }

    public function test_a_failed_batch_rolls_back_changes_and_audit_but_retains_one_safe_error(): void
    {
        $company = $this->company();
        $team = $this->writer->create(Team::class, ['TM_CONo' => $company->getKey(), 'TMCode' => 'OPS', 'TMName' => 'Operations']);
        $historyCount = FoundationLog::query()->count();
        try {
            $this->writer->transaction(null, function (FoundationChanges $changes) use ($team) {
                $changes->update($team, ['TMName' => 'Should roll back']);
                throw new RuntimeException('SECRET-PASSWORD and provider token must never be recorded');
            });
            $this->fail('The exception should be rethrown.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('SECRET-PASSWORD', $exception->getMessage());
        }
        $this->assertSame('Operations', $team->fresh()->TMName);
        $this->assertSame($historyCount, FoundationLog::query()->count());
        $this->assertSame(1, FoundationError::query()->count());
        $error = FoundationError::query()->firstOrFail();
        $this->assertSame('MSTeam', $error->FETable);
        $this->assertSame($team->getKey(), $error->FERecordNo);
        $this->assertFalse($error->FEIsExample);
        $this->assertStringNotContainsString('SECRET-PASSWORD', $error->toJson());
    }

    public function test_a_database_constraint_failure_is_recorded_without_a_successful_change(): void
    {
        $this->company();
        $count = FoundationLog::query()->count();
        try {
            $this->company();
            $this->fail('Duplicate company code should fail.');
        } catch (QueryException) {
            $this->assertSame(1, Company::query()->count());
        }
        $this->assertSame($count, FoundationLog::query()->count());
        $this->assertSame('DATABASE_WRITE_FAILED', FoundationError::query()->sole()->FECode);
        $this->assertSame(1, DB::table('MSNumberSequence')->where('NSPrefix', 'CO')->value('NSLastValue'));
    }

    public function test_failure_to_insert_the_audit_row_also_rolls_back_the_business_row(): void
    {
        FoundationLog::creating(function () {
            throw new RuntimeException('Simulated audit failure');
        });
        try {
            $this->company();
            $this->fail('The audit failure should abort the write.');
        } catch (RuntimeException) {
            $this->assertSame(0, Company::query()->count());
            $this->assertSame(0, FoundationLog::query()->count());
            $this->assertSame(1, FoundationError::query()->count());
            // A rolled-back create must not point to a future record reusing that number.
            $this->assertNull(FoundationError::query()->sole()->FERecordNo);
        } finally {
            FoundationLog::flushEventListeners();
            FoundationLog::clearBootedModels();
        }
    }

    public function test_secrets_are_hashed_or_hidden_and_excluded_from_audit_snapshots(): void
    {
        $this->seed(FoundationSeeder::class);
        $user = User::query()->where('USWorkerID', 'PT-1000')->firstOrFail();
        $this->writer->update($user, ['USPassword' => 'SECRET-password-for-test-only']);
        $invitation = Invitation::query()->firstOrFail();
        $hash = hash('sha256', 'SECRET-invitation-token');
        $this->writer->update($invitation, ['IVTokenHash' => $hash]);
        $allLogs = FoundationLog::query()->get()->toJson();
        foreach (['SECRET-', 'USPassword', 'IVTokenHash', $hash, $user->fresh()->USPassword] as $secret) {
            $this->assertStringNotContainsString($secret, $allLogs);
        }
        $this->assertStringNotContainsString('USPassword', $user->fresh()->toJson());
        $this->assertNotSame('SECRET-password-for-test-only', $user->fresh()->USPassword);
    }

    public function test_cross_team_pic_assignment_is_rejected_by_mysql(): void
    {
        $this->seed(FoundationSeeder::class);
        $team = Team::query()->where('TMCode', 'OPS')->firstOrFail();
        $wrongPic = User::query()->where('USWorkerID', 'PT-1025')->firstOrFail();
        $assignment = $team->picAssignment;
        try {
            $this->writer->update($assignment, ['TP_USNo' => $wrongPic->getKey()]);
            $this->fail('A PIC from a different team should be rejected.');
        } catch (QueryException) {
            $this->assertNotSame($wrongPic->getKey(), $assignment->fresh()->TP_USNo);
        }
        $this->assertSame(1, FoundationError::query()->where('FEIsExample', false)->count());
    }

    public function test_concurrent_allocations_do_not_issue_duplicate_numbers(): void
    {
        $code = <<<'PHP'
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $connection = config('database.default');
        config(['database.connections.'.$connection.'.database' => 'testing']);
        if ($connection !== 'mysql') { exit(2); }
        $numbers = [];
        for ($i = 0; $i < 8; $i++) $numbers[] = app(App\Support\Foundation\NumberAllocator::class)->next('TM');
        echo json_encode($numbers);
        PHP;
        $processes = [];
        for ($i = 0; $i < 4; $i++) {
            $process = new Process([PHP_BINARY, '-r', $code], base_path());
            $process->setTimeout(30);
            $process->start();
            $processes[] = $process;
        }
        $numbers = [];
        foreach ($processes as $process) {
            $process->wait();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
            $numbers = array_merge($numbers, json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR));
        }
        $this->assertCount(32, array_unique($numbers));
        foreach ($numbers as $number) {
            $this->assertMatchesRegularExpression('/^TM\d{8}$/', $number);
        }
        $this->assertSame(32, DB::table('MSNumberSequence')->where('NSPrefix', 'TM')->value('NSLastValue'));
    }

    public function test_foundation_migration_can_roll_back_without_removing_framework_tables(): void
    {
        $this->seed(FoundationSeeder::class);
        $this->artisan('migrate:rollback', ['--step' => 1, '--force' => true])->assertSuccessful();
        foreach (array_keys(config('foundation.models')) as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('sessions'));
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->assertSame(17, DB::table('MSNumberSequence')->count());
    }

    private function company(): Company
    {
        return $this->writer->create(Company::class, ['COCode' => 'TEST', 'COName' => 'Test company']);
    }

    private function counts(): array
    {
        return array_map(fn ($class) => $class::query()->count(), config('foundation.models'));
    }
}
