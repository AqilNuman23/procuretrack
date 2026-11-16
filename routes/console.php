<?php

use App\Rules\ApplicationPassword;
use App\Services\Access\ModuleAccess;
use App\Services\Access\ModuleCatalog;
use App\Services\RecoveryService;
use App\Support\Foundation\FoundationWriter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('foundation:admin-password {worker=PT-1000}', function () {
    $user = app(RecoveryService::class)->lookup($this->argument('worker'));
    if (! $user || ! app(ModuleAccess::class)->administrator($user)) {
        $this->error('No administrator matches that Worker ID in the configured company.');

        return 1;
    }
    $password = $this->secret('Choose a private application password (at least 12 characters)');
    $confirmation = $this->secret('Confirm password');
    if ($password !== $confirmation || Validator::make(['password' => $password], ['password' => ['required', 'string', new ApplicationPassword]])->fails()) {
        $this->error('Passwords must match, contain at least 12 characters, and fit within the 72-byte password limit.');

        return 1;
    }
    app(FoundationWriter::class)->update($user, ['USPassword' => $password, 'USLocalDemo' => false, 'USAuthVersion' => $user->USAuthVersion + 1], $user);
    $this->info('Private password set. Local demo credentials are disabled and existing sessions are invalidated.');

    return 0;
})->purpose('Set an administrator password through controlled console access');

Artisan::command('foundation:sync-modules', function () {
    app(ModuleCatalog::class)->sync();
    $this->info('Module names and definitions synchronized. Employee grants were not expanded.');
})->purpose('Synchronize the developer-maintained module registry without fictional data');
