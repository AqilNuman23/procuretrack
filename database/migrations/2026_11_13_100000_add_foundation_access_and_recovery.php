<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['MSModule' => 'MO', 'MSAccessGroup' => 'AG', 'TRGroupItem' => 'GI', 'TRDelegation' => 'DL', 'TRGroupMembership' => 'GM', 'TRAccessGrant' => 'AX', 'TRRecoveryRequest' => 'RQ', 'TRPasswordReset' => 'PS', 'TREmailVerification' => 'EV', 'TREmailHistory' => 'EH'];

    private function columns(Blueprint $table, string $prefix): void
    {
        $table->engine = 'InnoDB';
        $table->bigIncrements($prefix.'ID');
        $table->string($prefix.'No', 10)->unique();
        foreach (['CB', 'MB'] as $suffix) {
            $this->ref($table, $prefix.$suffix, 'MSUser', 'USNo', true);
        }
        $table->timestamp($prefix.'CD')->useCurrent();
        $table->timestamp($prefix.'MD')->useCurrent();
    }

    private function ref(Blueprint $table, string $column, string $target, string $key, bool $nullable = false): void
    {
        $table->string($column, 10)->nullable($nullable);
        $table->foreign($column)->references($key)->on($target)->restrictOnDelete()->restrictOnUpdate();
    }

    public function up(): void
    {
        Schema::table('MSUser', function (Blueprint $table) {
            $table->string('USLoginName', 50)->nullable()->unique();
            $table->string('USRecoveryEmail', 254)->nullable();
            $table->timestamp('USApprovedAt')->nullable();
            $table->unsignedInteger('USAuthVersion')->default(0);
            $table->unsignedInteger('USRecoveryVersion')->default(0);
            $table->boolean('USLocalDemo')->default(false);
        });
        DB::table('MSUser')->update(['USRecoveryEmail' => DB::raw('USContactEmail')]);
        DB::table('MSUser')->whereNotNull('USActivatedAt')->update(['USApprovedAt' => DB::raw('USActivatedAt')]);
        DB::table('MSUser')->join('TRInvitation', 'IV_USNo', '=', 'USNo')->join('TRReview', 'RV_IVNo', '=', 'IVNo')
            ->where('IVStatus', 'approved')->where('RVDecision', 'approved')->whereNull('USApprovedAt')
            ->update(['USApprovedAt' => DB::raw('RVDecidedAt')]);
        // Laravel infrastructure names remain standard; session identifiers now hold USNo.
        Schema::table('sessions', fn (Blueprint $table) => $table->string('user_id', 30)->nullable()->change());

        Schema::create('MSModule', function (Blueprint $table) {
            $this->columns($table, 'MO');
            $table->string('MOCode', 80)->unique();
            $table->string('MOName', 100);
            $table->string('MOParent_MONo', 10)->nullable();
            $table->boolean('MODelegable')->default(false);
            $table->boolean('MOActive')->default(true);
        });
        Schema::table('MSModule', fn (Blueprint $table) => $table->foreign('MOParent_MONo')->references('MONo')->on('MSModule')->restrictOnDelete()->restrictOnUpdate());
        Schema::create('MSAccessGroup', function (Blueprint $table) {
            $this->columns($table, 'AG');
            $this->ref($table, 'AG_CONo', 'MSCompany', 'CONo');
            $table->string('AGName', 100);
            $table->boolean('AGActive')->default(true);
            $table->unique(['AG_CONo', 'AGName']);
        });
        Schema::create('TRGroupItem', function (Blueprint $table) {
            $this->columns($table, 'GI');
            $this->ref($table, 'GI_AGNo', 'MSAccessGroup', 'AGNo');
            $this->ref($table, 'GI_MONo', 'MSModule', 'MONo');
            $table->enum('GILevel', ['read', 'edit']);
            $table->boolean('GIActive')->default(true);
            $table->unique(['GI_AGNo', 'GI_MONo']);
        });
        Schema::create('TRDelegation', function (Blueprint $table) {
            $this->columns($table, 'DL');
            $this->ref($table, 'DL_USNo', 'MSUser', 'USNo');
            $this->ref($table, 'DL_MONo', 'MSModule', 'MONo');
            $table->enum('DLMaxLevel', ['read', 'edit']);
            $table->boolean('DLActive')->default(true);
            $table->unique(['DL_USNo', 'DL_MONo']);
        });
        Schema::create('TRGroupMembership', function (Blueprint $table) {
            $this->columns($table, 'GM');
            $this->ref($table, 'GM_USNo', 'MSUser', 'USNo');
            $this->ref($table, 'GM_AGNo', 'MSAccessGroup', 'AGNo');
            $this->ref($table, 'GMGrantor_USNo', 'MSUser', 'USNo');
            $table->boolean('GMActive')->default(true);
            $table->unique(['GM_USNo', 'GM_AGNo', 'GMGrantor_USNo'], 'gm_assignment_unique');
        });
        Schema::create('TRAccessGrant', function (Blueprint $table) {
            $this->columns($table, 'AX');
            $this->ref($table, 'AX_USNo', 'MSUser', 'USNo');
            $this->ref($table, 'AX_MONo', 'MSModule', 'MONo');
            $this->ref($table, 'AXGrantor_USNo', 'MSUser', 'USNo');
            $this->ref($table, 'AX_DLNo', 'TRDelegation', 'DLNo', true);
            $this->ref($table, 'AX_GMNo', 'TRGroupMembership', 'GMNo', true);
            $table->enum('AXLevel', ['read', 'edit']);
            $table->boolean('AXActive')->default(true);
            $table->index(['AX_USNo', 'AX_MONo', 'AXActive']);
        });
        Schema::create('TRRecoveryRequest', function (Blueprint $table) {
            $this->columns($table, 'RQ');
            $this->ref($table, 'RQ_USNo', 'MSUser', 'USNo');
            $this->ref($table, 'RQReviewer_USNo', 'MSUser', 'USNo', true);
            $table->enum('RQStatus', ['pending', 'approved', 'rejected', 'completed']);
            $table->text('RQNewEmail')->nullable(); // encrypted cast; never a history snapshot
            $table->unsignedInteger('RQAuthVersion');
            $table->unsignedInteger('RQRecoveryVersion');
            $table->string('RQVerificationMethod', 30)->nullable();
            $table->text('RQReason')->nullable();
            $table->timestamp('RQReviewedAt')->nullable();
            $table->timestamp('RQExpiresAt');
        });
        Schema::create('TRPasswordReset', function (Blueprint $table) {
            $this->columns($table, 'PS');
            $this->ref($table, 'PS_USNo', 'MSUser', 'USNo');
            $this->ref($table, 'PS_RQNo', 'TRRecoveryRequest', 'RQNo', true);
            $table->char('PSTokenHash', 64)->unique();
            $table->unsignedInteger('PSAuthVersion');
            $table->unsignedInteger('PSRecoveryVersion');
            $table->timestamp('PSExpiresAt');
            $table->timestamp('PSUsedAt')->nullable();
            $table->timestamp('PSRevokedAt')->nullable();
        });
        Schema::create('TREmailVerification', function (Blueprint $table) {
            $this->columns($table, 'EV');
            $this->ref($table, 'EV_USNo', 'MSUser', 'USNo');
            $this->ref($table, 'EV_RQNo', 'TRRecoveryRequest', 'RQNo');
            $table->char('EVTokenHash', 64)->unique();
            $table->text('EVNewEmail');
            $table->unsignedInteger('EVAuthVersion');
            $table->unsignedInteger('EVRecoveryVersion');
            $table->timestamp('EVExpiresAt');
            $table->timestamp('EVUsedAt')->nullable();
        });
        Schema::create('TREmailHistory', function (Blueprint $table) {
            $this->columns($table, 'EH');
            $this->ref($table, 'EH_USNo', 'MSUser', 'USNo');
            $this->ref($table, 'EH_RQNo', 'TRRecoveryRequest', 'RQNo');
            $this->ref($table, 'EHApprover_USNo', 'MSUser', 'USNo');
            $table->text('EHOldEmail');
            $table->text('EHNewEmail');
            $table->timestamp('EHVerifiedAt');
        });
        $nextSequence = DB::table('MSNumberSequence')->where('NSPrefix', 'NS')->value('NSLastValue');
        foreach (self::TABLES as $name => $prefix) {
            Schema::table($name, fn (Blueprint $table) => $table->unique($prefix.'ID'));
            Schema::table($name, function (Blueprint $table) use ($prefix) {
                $table->dropPrimary();
                $table->primary($prefix.'No');
            });
            DB::table('MSNumberSequence')->insert(['NSNo' => 'NS'.str_pad((string) ++$nextSequence, 8, '0', STR_PAD_LEFT), 'NSPrefix' => $prefix, 'NSLastValue' => 0, 'NSCD' => now(), 'NSMD' => now()]);
        }
        DB::table('MSNumberSequence')->where('NSPrefix', 'NS')->update(['NSLastValue' => $nextSequence, 'NSMD' => now()]);
    }

    public function down(): void
    {
        foreach (array_reverse(array_keys(self::TABLES)) as $name) {
            Schema::dropIfExists($name);
        }
        DB::table('MSNumberSequence')->whereIn('NSPrefix', array_values(self::TABLES))->delete();
        DB::table('sessions')->where('user_id', 'like', 'US%')->delete();
        Schema::table('sessions', fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->nullable()->change());
        Schema::table('MSUser', function (Blueprint $table) {
            $table->dropUnique(['USLoginName']);
            $table->dropColumn(['USLoginName', 'USRecoveryEmail', 'USApprovedAt', 'USAuthVersion', 'USRecoveryVersion', 'USLocalDemo']);
        });
    }
};
