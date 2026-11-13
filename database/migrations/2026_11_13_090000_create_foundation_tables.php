<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // This frozen list is also used for safe, dependency-ordered rollback.
    private const TABLES = [
        'MSNumberSequence' => 'NS', 'MSCompany' => 'CO', 'MSCompanySetting' => 'CS',
        'MSTeam' => 'TM', 'MSUser' => 'US', 'TRTeamPIC' => 'TP',
        'MSRole' => 'RL', 'MSPermission' => 'PM', 'TRUserRole' => 'UR',
        'TRRolePermission' => 'RP', 'TRInvitation' => 'IV', 'TRReview' => 'RV',
        'TRMailbox' => 'MX', 'TRActivation' => 'AV', 'MSDepartment' => 'DP',
        'TRFoundationLog' => 'FL', 'TRFoundationError' => 'FE',
    ];

    private function columns(Blueprint $table, string $prefix): void
    {
        $table->engine = 'InnoDB';
        $table->bigIncrements($prefix.'ID');
        // ID remains auto-incrementing and unique; No is the relationship key.
        $table->string($prefix.'No', 10)->unique();
        $table->string($prefix.'CB', 10)->nullable();
        $table->timestamp($prefix.'CD')->useCurrent();
        $table->string($prefix.'MB', 10)->nullable();
        $table->timestamp($prefix.'MD')->useCurrent();
    }

    private function reference(Blueprint $table, string $column, string $target, string $key, bool $nullable = false): void
    {
        $table->string($column, 10)->nullable($nullable);
        $table->foreign($column)->references($key)->on($target)->restrictOnDelete()->restrictOnUpdate();
    }

    public function up(): void
    {
        Schema::create('MSNumberSequence', function (Blueprint $table) {
            $this->columns($table, 'NS');
            $table->string('NSPrefix', 2)->unique();
            $table->unsignedBigInteger('NSLastValue')->default(0);
        });
        Schema::create('MSCompany', function (Blueprint $table) {
            $this->columns($table, 'CO');
            $table->string('COCode', 30)->unique();
            $table->string('COName', 150);
            $table->string('COTimezone', 60)->default('Asia/Kuala_Lumpur');
            $table->char('COCurrency', 3)->default('MYR');
        });
        Schema::create('MSCompanySetting', function (Blueprint $table) {
            $this->columns($table, 'CS');
            $this->reference($table, 'CS_CONo', 'MSCompany', 'CONo');
            $table->string('CSKey', 100);
            $table->json('CSValue')->nullable();
            $table->unique(['CS_CONo', 'CSKey']);
        });
        Schema::create('MSTeam', function (Blueprint $table) {
            $this->columns($table, 'TM');
            $this->reference($table, 'TM_CONo', 'MSCompany', 'CONo');
            $table->string('TMCode', 30);
            $table->string('TMName', 150);
            $table->boolean('TMActive')->default(true);
            $table->unique(['TM_CONo', 'TMCode']);
            $table->unique(['TM_CONo', 'TMNo']);
        });
        Schema::create('MSUser', function (Blueprint $table) {
            $this->columns($table, 'US');
            $this->reference($table, 'US_CONo', 'MSCompany', 'CONo');
            $table->string('US_TMNo', 10)->nullable();
            $table->string('USName', 150);
            $table->string('USWorkerID', 50);
            $table->string('USContactEmail', 254);
            $table->string('USEmail', 254)->nullable();
            $table->string('USPassword')->nullable();
            $table->enum('USStatus', ['pending_activation', 'active', 'deactivated'])->default('pending_activation');
            $table->timestamp('USActivatedAt')->nullable();
            $table->timestamp('USDeactivatedAt')->nullable();
            $table->unique(['US_CONo', 'USWorkerID']);
            $table->unique(['US_CONo', 'USEmail']);
            $table->unique(['US_TMNo', 'USNo']);
            $table->foreign(['US_CONo', 'US_TMNo'])->references(['TM_CONo', 'TMNo'])->on('MSTeam')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::create('TRTeamPIC', function (Blueprint $table) {
            $this->columns($table, 'TP');
            $this->reference($table, 'TP_TMNo', 'MSTeam', 'TMNo');
            $this->reference($table, 'TP_USNo', 'MSUser', 'USNo');
            $table->unique('TP_TMNo');
            // A team's PIC must belong to that team. Active/role checks belong to onboarding.
            $table->foreign(['TP_TMNo', 'TP_USNo'])->references(['US_TMNo', 'USNo'])->on('MSUser')->restrictOnDelete()->restrictOnUpdate();
        });
        Schema::create('MSRole', function (Blueprint $table) {
            $this->columns($table, 'RL');
            $table->string('RLCode', 50)->unique();
            $table->string('RLName', 100);
        });
        Schema::create('MSPermission', function (Blueprint $table) {
            $this->columns($table, 'PM');
            $table->string('PMCode', 100)->unique();
            $table->string('PMName', 150);
        });
        Schema::create('TRUserRole', function (Blueprint $table) {
            $this->columns($table, 'UR');
            $this->reference($table, 'UR_USNo', 'MSUser', 'USNo');
            $this->reference($table, 'UR_RLNo', 'MSRole', 'RLNo');
            $table->unique(['UR_USNo', 'UR_RLNo']);
        });
        Schema::create('TRRolePermission', function (Blueprint $table) {
            $this->columns($table, 'RP');
            $this->reference($table, 'RP_RLNo', 'MSRole', 'RLNo');
            $this->reference($table, 'RP_PMNo', 'MSPermission', 'PMNo');
            $table->unique(['RP_RLNo', 'RP_PMNo']);
        });
        Schema::create('TRInvitation', function (Blueprint $table) {
            $this->columns($table, 'IV');
            $this->reference($table, 'IV_TMNo', 'MSTeam', 'TMNo');
            $this->reference($table, 'IVInviter_USNo', 'MSUser', 'USNo');
            $this->reference($table, 'IV_USNo', 'MSUser', 'USNo', true);
            $table->string('IVName', 150);
            $table->string('IVEmail', 254);
            $table->string('IVWorkerID', 50);
            $table->enum('IVStatus', ['invited', 'awaiting_review', 'approved', 'rejected', 'expired', 'revoked']);
            $table->char('IVTokenHash', 64)->nullable()->unique();
            $table->timestamp('IVExpiresAt');
            $table->timestamp('IVAcceptedAt')->nullable();
            $table->timestamp('IVRevokedAt')->nullable();
            $table->index(['IV_TMNo', 'IVStatus']);
            $table->index('IVEmail');
        });
        Schema::create('TRReview', function (Blueprint $table) {
            $this->columns($table, 'RV');
            $this->reference($table, 'RV_IVNo', 'TRInvitation', 'IVNo');
            $this->reference($table, 'RV_USNo', 'MSUser', 'USNo');
            $table->enum('RVDecision', ['approved', 'rejected']);
            $table->text('RVReason')->nullable();
            $table->timestamp('RVDecidedAt');
        });
        Schema::create('TRMailbox', function (Blueprint $table) {
            $this->columns($table, 'MX');
            $this->reference($table, 'MX_USNo', 'MSUser', 'USNo');
            $this->reference($table, 'MX_IVNo', 'TRInvitation', 'IVNo', true);
            $table->string('MXEmail', 254)->nullable()->unique();
            $table->string('MXProvider', 50)->default('mock');
            $table->boolean('MXIsMock')->default(true);
            $table->enum('MXStatus', ['not_started', 'pending', 'ready', 'failed'])->default('not_started');
            $table->string('MXIdempotencyKey', 64)->unique();
            $table->string('MXProviderReference', 150)->nullable();
            $table->string('MXFailureCode', 80)->nullable();
            $table->unsignedInteger('MXAttempts')->default(0);
            $table->timestamp('MXReadyAt')->nullable();
            $table->unique('MX_USNo');
        });
        Schema::create('TRActivation', function (Blueprint $table) {
            $this->columns($table, 'AV');
            $this->reference($table, 'AV_USNo', 'MSUser', 'USNo');
            $table->char('AVTokenHash', 64)->nullable()->unique();
            $table->timestamp('AVExpiresAt');
            $table->timestamp('AVUsedAt')->nullable();
            $table->timestamp('AVRevokedAt')->nullable();
        });
        Schema::create('MSDepartment', function (Blueprint $table) {
            $this->columns($table, 'DP');
            $this->reference($table, 'DP_CONo', 'MSCompany', 'CONo');
            $table->string('DPCode', 30);
            $table->string('DPName', 150);
            $table->boolean('DPActive')->default(true);
            $table->unique(['DP_CONo', 'DPCode']);
        });
        Schema::create('TRFoundationLog', function (Blueprint $table) {
            $this->columns($table, 'FL');
            $table->enum('FLAction', ['created', 'updated']);
            $table->string('FLTable', 64);
            $table->string('FLRecordNo', 10);
            $table->json('FLBefore')->nullable();
            $table->json('FLAfter')->nullable();
            $table->index(['FLTable', 'FLRecordNo']);
        });
        Schema::create('TRFoundationError', function (Blueprint $table) {
            $this->columns($table, 'FE');
            $table->uuid('FECorrelation')->unique();
            $table->enum('FEOperation', ['created', 'updated']);
            $table->string('FETable', 64);
            $table->string('FERecordNo', 10)->nullable();
            $table->string('FEException', 255);
            $table->string('FECode', 50);
            $table->string('FEMessage', 255);
            $table->boolean('FEIsExample')->default(false);
            $table->index(['FETable', 'FERecordNo']);
        });

        foreach (self::TABLES as $name => $prefix) {
            // MySQL requires an index for the auto-increment ID even after No becomes PK.
            Schema::table($name, fn (Blueprint $table) => $table->unique($prefix.'ID'));
            Schema::table($name, function (Blueprint $table) use ($prefix) {
                $table->dropPrimary();
                $table->primary($prefix.'No');
            });
        }
        foreach (self::TABLES as $name => $prefix) {
            Schema::table($name, function (Blueprint $table) use ($prefix) {
                $table->foreign($prefix.'CB')->references('USNo')->on('MSUser')->restrictOnDelete()->restrictOnUpdate();
                $table->foreign($prefix.'MB')->references('USNo')->on('MSUser')->restrictOnDelete()->restrictOnUpdate();
            });
        }
        // Pre-create one lockable counter per table; no MAX(No)+1 races.
        foreach (array_values(self::TABLES) as $index => $prefix) {
            DB::table('MSNumberSequence')->insert([
                'NSNo' => 'NS'.str_pad((string) ($index + 1), 8, '0', STR_PAD_LEFT),
                'NSPrefix' => $prefix,
                'NSLastValue' => $prefix === 'NS' ? count(self::TABLES) : 0,
                'NSCD' => now(), 'NSMD' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Remove actor links first; they point back to MSUser from every table.
        foreach (self::TABLES as $name => $prefix) {
            Schema::table($name, function (Blueprint $table) use ($prefix) {
                $table->dropForeign([$prefix.'CB']);
                $table->dropForeign([$prefix.'MB']);
            });
        }
        foreach (array_reverse(array_keys(self::TABLES)) as $name) {
            Schema::dropIfExists($name);
        }
    }
};
