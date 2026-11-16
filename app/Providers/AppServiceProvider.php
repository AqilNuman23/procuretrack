<?php

namespace App\Providers;

use App\Models\Foundation\Module;
use App\Models\Foundation\RecoveryRequest;
use App\Models\Foundation\User;
use App\Policies\FoundationUserPolicy;
use App\Policies\ModulePolicy;
use App\Policies\RecoveryRequestPolicy;
use App\Services\Access\ModuleAccess;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap(config('foundation.models', []));
        View::composer('foundation.*', fn ($view) => $view->with(['actor' => auth()->user(), 'access' => app(ModuleAccess::class), 'registry' => config('modules')]));
        Gate::policy(Module::class, ModulePolicy::class);
        Gate::policy(RecoveryRequest::class, RecoveryRequestPolicy::class);
        Gate::policy(User::class, FoundationUserPolicy::class);
        Gate::define('manage-access', fn (User $user) => app(ModuleAccess::class)->eligible($user) && (app(ModuleAccess::class)->administrator($user) || app(ModuleAccess::class)->pic($user)));
        Gate::define('manage-recovery', fn (User $user) => app(ModuleAccess::class)->eligible($user) && (app(ModuleAccess::class)->administrator($user) || app(ModuleAccess::class)->pic($user)));
        Gate::define('view-security-history', fn (User $user) => app(ModuleAccess::class)->eligible($user) && app(ModuleAccess::class)->administrator($user));
        $identity = fn (Request $request, string $field) => $request->ip().'|'.hash('sha256', is_string($request->input($field)) ? strtolower(substr(trim($request->input($field)), 0, 50)) : 'invalid');
        RateLimiter::for('account-login', fn (Request $request) => [Limit::perMinute(30)->by($request->ip()), Limit::perMinute(6)->by($identity($request, 'login'))]);
        RateLimiter::for('account-recovery', fn (Request $request) => [Limit::perMinute(20)->by($request->ip()), Limit::perMinute(3)->by($identity($request, 'worker_id'))]);
    }
}
