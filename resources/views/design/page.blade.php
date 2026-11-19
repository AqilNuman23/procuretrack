@php
    $url = fn ($key) => route('design', ['page' => $key]);
    $ui = $design['ui'];
    $module = $screen['module'] ? $design['modules'][$screen['module']] : null;
    $auth = $screen['auth'] ?? false;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $screen['title'] }} · {{ $design['brand'] }}</title>
    <link rel="stylesheet" href="{{ asset('design-assets/prototype.css') }}">
    <script src="{{ asset('design-assets/searchable-select.js') }}" defer></script>
    <script src="{{ asset('design-assets/form-errors.js') }}" defer></script>
    <script src="{{ asset('design-assets/prototype.js') }}" defer></script>
</head>
<body class="{{ $auth ? 'auth-page' : '' }} {{ $module && !$auth ? 'with-sidebar' : '' }}">
<a href="#main" class="skip-link">Skip to content</a>
@if($auth)
    <div class="auth-story">
        <a class="brand" href="{{ $url('hub') }}"><span class="brand-mark">p<span>.</span></span>{{ $design['brand'] }}</a>
        <div class="auth-pitch"><span class="eyebrow">A BETTER WAY TO WORK</span><h2>Good work starts<br>with connection.</h2><p>One workspace for your people, your processes, and what comes next.</p><div class="orbit" aria-hidden="true"><div class="orbit-core"><x-design.icon name="grid" /></div><span class="orbit-node one"><x-design.icon name="users" /></span><span class="orbit-node two"><x-design.icon name="shield" /></span><span class="orbit-node three"><x-design.icon name="mail" /></span></div></div>
        <span class="muted">Built around people. Designed for progress.</span>
    </div>
@else
    @if($module)
    <div class="drawer-backdrop" data-drawer-close hidden></div>
    <aside class="sidebar" id="module-navigation" aria-label="Module navigation">
        <div class="sidebar-brand"><a class="brand" href="{{ $url('hub') }}"><span class="brand-mark">p<span>.</span></span>{{ $design['brand'] }}</a><button class="icon-button mobile-only" data-drawer-close aria-label="Close navigation"><x-design.icon name="close" /></button></div>
        <div class="nav-label">{{ $module['name'] }}</div>
        <nav>
            @foreach($module['links'] as $link)
                @php($active = $pageKey === $link || ($screen['parent'] ?? null) === $link)
                <a href="{{ $url($link) }}" class="nav-link {{ $active ? 'active' : '' }}" @if($active) aria-current="page" @endif><x-design.icon :name="['people'=>'home','teams'=>'users','users'=>'users','invitations'=>'mail','reviews'=>'shield','mailboxes'=>'mail','activity'=>'clock','settings'=>'home','company'=>'grid','roles'=>'shield','preferences'=>'settings','help'=>'home','manual'=>'book','faq'=>'book','support'=>'mail'][$link] ?? 'grid'" /><span>{{ $design['pages'][$link]['title'] }}</span>@if($link === 'reviews')<span class="nav-count">4</span>@endif</a>
            @endforeach
        </nav>
        <div class="sidebar-bottom"><div class="workspace-icon">MW</div><div><strong>{{ $design['company'] }}</strong><small>Company workspace</small></div><span class="online-dot" title="Preview workspace"></span></div>
    </aside>
    @endif
    <header class="topbar">
        <div class="topbar-start">@if($module)<button class="icon-button mobile-only" data-drawer-open aria-controls="module-navigation" aria-expanded="false" aria-label="Open navigation"><x-design.icon name="menu" /></button><span class="topbar-module">{{ $module['name'] }}</span>@else<a class="brand" href="{{ $url('hub') }}"><span class="brand-mark">p<span>.</span></span>{{ $design['brand'] }}</a>@endif</div>
        <div class="topbar-actions"><a class="quiet-link gallery-link" href="{{ $url('pages') }}"><x-design.icon name="grid" />{{ $ui['gallery'] }}</a><span class="topbar-divider"></span><details class="account-menu"><summary><span class="avatar">{{ $design['person']['initials'] }}</span><span class="account-name">{{ $design['person']['name'] }}<small>{{ $design['person']['role'] }}</small></span><span aria-hidden="true">⌄</span></summary><div class="dropdown"><a href="{{ $url('profile') }}">{{ $ui['profile'] }}</a><a href="{{ $url('mailbox-guide') }}">{{ $design['pages']['mailbox-guide']['title'] }}</a><a href="{{ $url('login') }}">{{ $ui['logout'] }} · preview</a></div></details></div>
    </header>
@endif
<main id="main" class="{{ $auth ? 'auth-main' : 'main-content' }}">
    <div class="preview-bar"><span><span class="preview-dot"></span>{{ $ui['preview'] }}</span><span>{{ $ui['preview_note'] }}</span>@if($auth)<a href="{{ $url('pages') }}">{{ $ui['gallery'] }}</a>@endif</div>
    @if(!$auth && $screen['type'] !== 'hub')<div class="breadcrumb"><a href="{{ $url('hub') }}">{{ $ui['all_modules'] }}</a><span>/</span>@if($module)<a href="{{ $url($module['home']) }}">{{ $module['name'] }}</a><span>/</span>@endif<span>{{ $screen['title'] }}</span></div>@endif
    <div class="page-heading {{ $screen['type'] === 'hub' ? 'hub-heading' : '' }}">
        <div>@if($screen['type'] === 'hub')<div class="eyebrow">YOUR WORKSPACE</div>@endif<h1>{{ $screen['title'] }}</h1><p>{{ $screen['description'] }}</p></div>
        @if(isset($screen['action']))<a class="button primary" href="{{ $url($screen['action'][1]) }}"><x-design.icon name="plus" />{{ $screen['action'][0] }}</a>@endif
    </div>
    @if(isset($screen['notice']))<div class="notice"><x-design.icon name="alert" /><span>{{ $screen['notice'] }}</span></div>@endif
    @if($screen['tabs'] ?? false)<nav class="tabs" aria-label="Account sections">@foreach(['profile', 'password-change', 'mailbox-guide'] as $tab)<a class="{{ $pageKey === $tab ? 'selected' : '' }}" href="{{ $url($tab) }}">{{ $design['pages'][$tab]['title'] }}</a>@endforeach</nav>@endif
    @include('design.partials.'.$screen['type'])
    @if(!$auth)<footer class="page-footer"><span>{{ $design['brand'] }} <span class="muted">/</span> {{ $design['company'] }}</span><a href="{{ $url('help') }}">Need a hand? {{ $design['modules']['help']['name'] }} <span aria-hidden="true">↗</span></a></footer>@endif
</main>
<div class="toast" role="status" aria-live="polite" hidden></div>
<dialog id="preview-dialog" aria-labelledby="dialog-title"><div class="dialog-icon"><x-design.icon name="shield" /></div><h2 id="dialog-title">Confirm preview action</h2><p id="dialog-description">This changes the preview only. No data is saved or sent.</p><form id="dialog-form"><label class="field" id="reason-field" hidden><span>Reason <span class="required">*</span></span><textarea name="reason" rows="3"></textarea></label><div class="dialog-actions"><button type="button" class="button secondary" data-dialog-close>{{ $ui['cancel'] }}</button><button type="submit" class="button primary">Confirm · preview</button></div></form></dialog>
<x-shared.form-error-dialog />
</body>
</html>
