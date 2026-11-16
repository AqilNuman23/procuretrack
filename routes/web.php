<?php

use App\Http\Controllers\Foundation\AccessController;
use App\Http\Controllers\Foundation\ActivityController;
use App\Http\Controllers\Foundation\LoginController;
use App\Http\Controllers\Foundation\RecoveryController;
use App\Http\Controllers\Foundation\WorkspaceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'workspace' : 'login'));

Route::middleware('account.headers')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->middleware('guest')->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware(['guest', 'throttle:account-login'])->name('login.store');
    Route::get('/forgot-password', [RecoveryController::class, 'forgot'])->name('password.request');
    Route::post('/forgot-password', [RecoveryController::class, 'send'])->middleware('throttle:account-recovery')->name('password.email');
    Route::get('/forgot-invitation-email', [RecoveryController::class, 'assistance'])->name('recovery.request');
    Route::post('/forgot-invitation-email', [RecoveryController::class, 'requestAssistance'])->middleware('throttle:account-recovery')->name('recovery.store');
    Route::get('/reset-password/{token}', [RecoveryController::class, 'resetForm'])->middleware('throttle:30,1')->name('password.reset');
    Route::post('/reset-password', [RecoveryController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
    Route::get('/verify-recovery-email/{token}', [RecoveryController::class, 'verifyForm'])->middleware('throttle:30,1')->name('recovery.verify');
    Route::post('/verify-recovery-email', [RecoveryController::class, 'verify'])->middleware('throttle:10,1')->name('recovery.verify.store');
    Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware(['auth', 'foundation.active'])->group(function () {
        Route::get('/workspace', [WorkspaceController::class, 'index'])->name('workspace');
        Route::get('/modules/{module:MOCode}', [WorkspaceController::class, 'module'])->name('modules.show');
        Route::post('/company', [WorkspaceController::class, 'company'])->middleware('module:settings.company,edit')->name('company.update');
        Route::get('/users/{user}', [WorkspaceController::class, 'user'])->name('users.show');
        Route::get('/my-account', [WorkspaceController::class, 'profile'])->name('profile');
        Route::post('/my-account/password', [WorkspaceController::class, 'password'])->middleware('throttle:5,1')->name('profile.password');
        Route::get('/api/v1/me', fn (Request $request) => ['no' => $request->user()->USNo, 'name' => $request->user()->USName, 'worker_id' => $request->user()->USWorkerID])->name('account.me');
        Route::prefix('access')->name('access.')->middleware('module:settings.access')->group(function () {
            Route::get('/', [AccessController::class, 'index'])->name('index');
            Route::post('/groups', [AccessController::class, 'group'])->name('groups.store');
            Route::post('/delegations', [AccessController::class, 'delegation'])->name('delegations.store');
            Route::post('/individual', [AccessController::class, 'direct'])->name('direct');
            Route::post('/group-assignments', [AccessController::class, 'assignGroup'])->name('groups.assign');
            Route::post('/grants/{grant}/revoke', [AccessController::class, 'revoke'])->name('grants.revoke');
            Route::post('/groups/{group}/deactivate', [AccessController::class, 'deactivateGroup'])->name('groups.deactivate');
            Route::post('/memberships/{membership}/revoke', [AccessController::class, 'revokeMembership'])->name('memberships.revoke');
        });
        Route::get('/recovery-requests', [RecoveryController::class, 'index'])->middleware('module:people.recovery')->name('recovery.index');
        Route::get('/recovery-requests/{recoveryRequest}', [RecoveryController::class, 'show'])->name('recovery.show');
        Route::post('/recovery-requests/{recoveryRequest}/review', [RecoveryController::class, 'review'])->middleware('throttle:20,1')->name('recovery.review');
        Route::post('/recovery-requests/{recoveryRequest}/resend', [RecoveryController::class, 'resend'])->middleware('throttle:3,1')->name('recovery.resend');
        Route::get('/activity', [ActivityController::class, 'index'])->middleware('module:people.history')->name('activity.index');
    });
});

// Local-only, fictional UI previews. No authentication or business actions run here.
if (app()->environment(['local', 'testing'])) {
    Route::view('/status-demo', 'app');
    Route::get('/design/{page?}', function (string $page = 'hub') {
        $design = config('design');
        abort_unless(isset($design['pages'][$page]), 404);

        return view('design.page', [
            'design' => $design,
            'screen' => $design['pages'][$page],
            'pageKey' => $page,
        ]);
    })->name('design');
}
