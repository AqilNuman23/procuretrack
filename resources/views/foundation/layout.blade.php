@php
    $authPage = $authPage ?? false;
    $actor = auth()->user();
    $access = app(\App\Services\Access\ModuleAccess::class);
    $registry = config('modules');
    $section = request()->routeIs('access.*','departments.*') ? 'settings' : (request()->routeIs('recovery.*','users.*','teams.*','invitations.*','mailboxes.*','activity.*') ? 'people' : null);
    $sidebarRoot = isset($currentModule) ? ($currentModule->parent ?? $currentModule) : ($section && $actor ? \App\Models\Foundation\Module::where('MOCode', $section)->first() : null);
    $companyName = $actor?->company?->COName;
    $switchAdministrator = !$authPage && $actor ? app(\App\Services\Impersonation::class)->administrator(request()) : null;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" data-theme="{{ request()->attributes->get('theme', 'dark') }}"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="color-scheme" content="{{ request()->attributes->get('theme', 'dark') }}"><meta name="robots" content="noindex, nofollow"><title>{{ __($title) }} · {{ config('design.brand', 'ProcureTrack') }}</title><link rel="stylesheet" href="{{ asset('design-assets/prototype.css') }}"><link rel="stylesheet" href="{{ asset('foundation/application.css') }}"><link rel="stylesheet" href="{{ asset('foundation/themes.css') }}"><script src="{{ asset('design-assets/form-errors.js') }}" defer></script><script src="{{ asset('foundation/application.js') }}" defer></script><script src="{{ asset('design-assets/searchable-select.js') }}" defer></script></head>
<body class="{{ $authPage ? 'auth-page' : ($sidebarRoot ? 'with-sidebar' : '') }}">
<a href="#main" class="skip-link">{{ __('Skip to content') }}</a>
@if($authPage)
<div class="auth-preferences"><x-foundation.display-preferences /></div>
<aside class="auth-story"><a class="brand" href="{{ route('login') }}"><span class="brand-mark">p<span>.</span></span>{{ config('design.brand') }}</a><div class="auth-pitch"><span class="eyebrow">{{ __('A BETTER WAY TO WORK') }}</span><h2>{{ __('Good work starts') }}<br>{{ __('with connection.') }}</h2><p>{{ __('One workspace for your people, your processes, and what comes next.') }}</p><div class="orbit" aria-hidden="true"><div class="orbit-core"><x-design.icon name="grid" /></div><span class="orbit-node one"><x-design.icon name="users" /></span><span class="orbit-node two"><x-design.icon name="shield" /></span><span class="orbit-node three"><x-design.icon name="mail" /></span></div></div><span class="muted">{{ __('Built around people. Designed for progress.') }}</span></aside>
@else
@if($sidebarRoot)
<div class="drawer-backdrop" data-drawer-close hidden></div><aside class="sidebar" id="module-navigation" aria-label="{{ __('Module navigation') }}"><div class="sidebar-brand"><a class="brand" href="{{ route('workspace') }}"><span class="brand-mark">p<span>.</span></span>{{ config('design.brand') }}</a><button class="icon-button mobile-only" data-drawer-close aria-label="{{ __('Close navigation') }}"><x-design.icon name="close" /></button></div><div class="nav-label">{{ __($sidebarRoot->MOName) }}</div><nav><a class="nav-link" href="{{ route('modules.show', $sidebarRoot->MOCode) }}"><x-design.icon name="home" />{{ __('Overview') }}</a>@foreach($sidebarRoot->children as $child)@if($access->can($actor, $child))<a class="nav-link {{ isset($currentModule) && $currentModule->MONo === $child->MONo ? 'active' : '' }}" href="{{ route('modules.show', $child->MOCode) }}" @if(isset($currentModule) && $currentModule->MONo === $child->MONo) aria-current="page" @endif><x-design.icon name="grid" />{{ __($child->MOName) }}</a>@endif @endforeach</nav><div class="sidebar-bottom"><span class="workspace-icon">PT</span><div><strong>{{ $companyName }}</strong><small>{{ __('Company workspace') }}</small></div></div></aside>
@endif
<header class="topbar"><div class="topbar-start">@if($sidebarRoot)<button class="icon-button mobile-only" data-drawer-open aria-controls="module-navigation" aria-expanded="false" aria-label="{{ __('Open navigation') }}"><x-design.icon name="menu" /></button><span class="topbar-module">{{ __($sidebarRoot->MOName) }}</span>@else<a class="brand" href="{{ route('workspace') }}"><span class="brand-mark">p<span>.</span></span>{{ config('design.brand') }}</a>@endif</div><div class="topbar-actions"><x-foundation.display-preferences />@if($switchAdministrator)<button class="button secondary" type="button" data-switch-open>{{ __('Switch user') }}</button>@endif<details class="account-menu"><summary><span class="avatar">{{ mb_substr($actor->USName, 0, 1) }}</span><span class="account-name">{{ $actor->USName }}<small>{{ $actor->USWorkerID }}</small></span><span aria-hidden="true">⌄</span></summary><div class="dropdown"><a href="{{ route('profile') }}">{{ __('My account') }}</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-button" type="submit">{{ __('Logout') }}</button></form></div></details></div></header>
@endif
<main id="main" class="{{ $authPage ? 'auth-main' : 'main-content' }} live-content">
@if(!$authPage && session()->has('impersonation'))
<div class="notice impersonation-banner" role="status"><div><strong>{{ __('Acting as :name (:worker)', ['name' => $actor->USName, 'worker' => $actor->USWorkerID]) }}</strong><p>{{ __('You are using this person’s permissions. Changes affect real records.') }}</p></div><form method="POST" action="{{ route('impersonation.stop') }}">@csrf<button class="button secondary" type="submit">{{ __('Return to administrator') }}</button></form></div>
@endif
@if(!$authPage)<div class="breadcrumb"><a href="{{ route('workspace') }}">{{ __('All modules') }}</a><span>/</span><span>{{ __($title) }}</span></div>@endif
<div class="page-heading {{ request()->routeIs('workspace') ? 'hub-heading' : '' }}">
    <div>
        @if(request()->routeIs('workspace'))<span class="eyebrow">{{ __('YOUR WORKSPACE') }}</span>@endif
        <div class="workspace-page-title">
            <h1>{{ __($title) }}</h1>
            @if(!$authPage && (filled($description ?? null) || $__env->hasSection('page-help')))
                <details class="page-information" data-page-information>
                    <summary class="icon-button" aria-label="{{ __('Page information') }}"><x-design.icon name="info" /></summary>
                    <div class="page-information-content">
                        @hasSection('page-help')
                            @yield('page-help')
                        @else
                            <p>{{ __($description) }}</p>
                        @endif
                    </div>
                </details>
            @endif
        </div>
        @if($authPage && filled($description ?? null))<p>{{ __($description) }}</p>@endif
    </div>
    @yield('page-actions')
</div>
@if(session('status'))<div class="notice" role="status"><x-design.icon name="check" /><span>{{ __(session('status')) }}</span></div>@endif
@if($errors->any())<div class="notice error-notice" role="alert"><x-design.icon name="alert" /><div><strong>{{ __('Please check the following.') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ __($error) }}</li>@endforeach</ul></div></div>@endif
@yield('content')
@if(!$authPage)<footer class="page-footer"><span>{{ config('design.brand') }} / {{ $companyName }}</span><a href="{{ route('modules.show', 'help.manual') }}">{{ __('Help Centre ↗') }}</a></footer>@endif
</main>
@if($switchAdministrator)
<dialog id="switch-user-dialog" class="switch-user-dialog" aria-labelledby="switch-user-title" data-users-url="{{ route('impersonation.users') }}">
<div class="switch-user-heading"><h2 id="switch-user-title">{{ __('Switch user') }}</h2><button class="icon-button" type="button" data-switch-close aria-label="{{ __('Close user selection') }}"><x-design.icon name="close" /></button></div>
<p>{{ __('Choose an active account to use its permissions. Changes affect real records. You can return to your administrator account at any time.') }}</p>
<form method="GET" data-switch-search><label class="field"><span>{{ __('Search name or Worker ID') }}</span><input name="search" maxlength="150" autocomplete="off" placeholder="{{ __('Find a person') }}"></label><button class="button secondary" type="submit">{{ __('Search') }}</button></form>
<p data-switch-state role="status" aria-live="polite"></p><button class="button secondary" type="button" data-switch-retry hidden>{{ __('Retry loading users') }}</button>
<form method="POST" action="{{ route('impersonation.start') }}">@csrf<label class="field"><span>{{ __('User') }}</span><select name="user" required size="6" data-switch-users aria-describedby="switch-user-help"></select></label><p id="switch-user-help">{{ __('Up to 50 matching active accounts from your company are shown. Search to narrow the list.') }}</p><div class="form-actions"><button class="button primary" type="submit" data-switch-submit disabled>{{ __('Use selected account') }}</button><button class="button secondary" type="button" data-switch-close>{{ __('Cancel') }}</button></div></form>
</dialog>
@endif
<script id="ui-messages" type="application/json">{!! json_encode(collect(['Confirm', 'Your session or account access changed. Sign in again before submitting.', 'You don’t have permission to submit this form.', 'Your form session expired. Reload this page before submitting again.', 'Too many attempts. Wait a moment before trying again.', 'We couldn’t confirm the result. Check whether your changes were saved before trying again.', 'Please check the information you entered.', 'Search options', 'No matching options.', 'Choose an option', 'Choose options', 'Show', 'Hide', 'Show password', 'Hide password', 'Saving, please wait…', 'Loading users…', 'Select a person to continue.', 'No active accounts match. Try another search.', 'Could not load users. Retry or refresh if your session has expired.'])->mapWithKeys(fn ($text) => [$text => __($text)]), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<dialog class="confirmation-dialog" data-confirmation-dialog aria-labelledby="confirmation-title" aria-describedby="confirmation-message">
    <h2 id="confirmation-title">{{ __('Confirm action') }}</h2>
    <p id="confirmation-message" data-confirmation-message></p>
    <div class="dialog-actions">
        <button class="button secondary" type="button" data-confirmation-cancel autofocus>{{ __('Cancel') }}</button>
        <button class="button primary" type="button" data-confirmation-submit>{{ __('Confirm') }}</button>
    </div>
</dialog>
<x-shared.form-error-dialog />
</body></html>
