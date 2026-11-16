@php
    $authPage = $authPage ?? false;
    $actor = auth()->user();
    $access = app(\App\Services\Access\ModuleAccess::class);
    $registry = config('modules');
    $section = request()->routeIs('access.*','departments.*') ? 'settings' : (request()->routeIs('recovery.*','users.*','teams.*','invitations.*','mailboxes.*','activity.*') ? 'people' : null);
    $sidebarRoot = isset($currentModule) ? ($currentModule->parent ?? $currentModule) : ($section && $actor ? \App\Models\Foundation\Module::where('MOCode', $section)->first() : null);
    $companyName = $actor?->company?->COName;
@endphp
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="color-scheme" content="dark"><meta name="robots" content="noindex, nofollow"><title>{{ $title }} · {{ config('design.brand', 'ProcureTrack') }}</title><link rel="stylesheet" href="{{ asset('design-assets/prototype.css') }}"><link rel="stylesheet" href="{{ asset('foundation/application.css') }}"><script src="{{ asset('foundation/application.js') }}" defer></script></head>
<body class="{{ $authPage ? 'auth-page' : ($sidebarRoot ? 'with-sidebar' : '') }}">
<a href="#main" class="skip-link">Skip to content</a>
@if($authPage)
<aside class="auth-story"><a class="brand" href="{{ route('login') }}"><span class="brand-mark">p<span>.</span></span>{{ config('design.brand') }}</a><div class="auth-pitch"><span class="eyebrow">A BETTER WAY TO WORK</span><h2>Good work starts<br>with connection.</h2><p>One workspace for your people, your processes, and what comes next.</p><div class="orbit" aria-hidden="true"><div class="orbit-core"><x-design.icon name="grid" /></div><span class="orbit-node one"><x-design.icon name="users" /></span><span class="orbit-node two"><x-design.icon name="shield" /></span><span class="orbit-node three"><x-design.icon name="mail" /></span></div></div><span class="muted">Built around people. Designed for progress.</span></aside>
@else
@if($sidebarRoot)
<div class="drawer-backdrop" data-drawer-close hidden></div><aside class="sidebar" id="module-navigation" aria-label="Module navigation"><div class="sidebar-brand"><a class="brand" href="{{ route('workspace') }}"><span class="brand-mark">p<span>.</span></span>{{ config('design.brand') }}</a><button class="icon-button mobile-only" data-drawer-close aria-label="Close navigation"><x-design.icon name="close" /></button></div><a class="all-modules" href="{{ route('workspace') }}"><x-design.icon name="grid" />All modules<x-design.icon name="chevron" /></a><div class="nav-label">{{ $sidebarRoot->MOName }}</div><nav><a class="nav-link" href="{{ route('modules.show', $sidebarRoot->MOCode) }}"><x-design.icon name="home" />Overview</a>@foreach($sidebarRoot->children as $child)@if($access->can($actor, $child))<a class="nav-link {{ isset($currentModule) && $currentModule->MONo === $child->MONo ? 'active' : '' }}" href="{{ route('modules.show', $child->MOCode) }}" @if(isset($currentModule) && $currentModule->MONo === $child->MONo) aria-current="page" @endif><x-design.icon name="grid" />{{ $child->MOName }}</a>@endif @endforeach</nav><div class="sidebar-bottom"><span class="workspace-icon">PT</span><div><strong>{{ $companyName }}</strong><small>Company workspace</small></div></div></aside>
@endif
<header class="topbar"><div class="topbar-start">@if($sidebarRoot)<button class="icon-button mobile-only" data-drawer-open aria-controls="module-navigation" aria-expanded="false" aria-label="Open navigation"><x-design.icon name="menu" /></button><span class="topbar-module">{{ $sidebarRoot->MOName }}</span>@else<a class="brand" href="{{ route('workspace') }}"><span class="brand-mark">p<span>.</span></span>{{ config('design.brand') }}</a>@endif</div><details class="account-menu"><summary><span class="avatar">{{ mb_substr($actor->USName, 0, 1) }}</span><span class="account-name">{{ $actor->USName }}<small>{{ $actor->USWorkerID }}</small></span><span aria-hidden="true">⌄</span></summary><div class="dropdown"><a href="{{ route('profile') }}">My account</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-button" type="submit">Logout</button></form></div></details></header>
@endif
<main id="main" class="{{ $authPage ? 'auth-main' : 'main-content' }} live-content">
@if(!$authPage)<div class="breadcrumb"><a href="{{ route('workspace') }}">All modules</a><span>/</span><span>{{ $title }}</span></div>@endif
<div class="page-heading {{ request()->routeIs('workspace') ? 'hub-heading' : '' }}"><div>@if(request()->routeIs('workspace'))<span class="eyebrow">YOUR WORKSPACE</span>@endif<h1>{{ $title }}</h1><p>{{ $description ?? '' }}</p></div></div>
@if(session('status'))<div class="notice" role="status"><x-design.icon name="check" /><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="notice error-notice" role="alert"><x-design.icon name="alert" /><div><strong>Please check the following.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
@yield('content')
@if(!$authPage)<footer class="page-footer"><span>{{ config('design.brand') }} / {{ $companyName }}</span><a href="{{ route('modules.show', 'help.manual') }}">Help Centre ↗</a></footer>@endif
</main>
</body></html>
