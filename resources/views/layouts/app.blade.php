@php
    $nav = [
        ['overview', 'dashboard_customize', 'Overview', 'Dashboard & key metrics', 'dashboard home metrics revenue summary'],
        ['analytics', 'show_chart', 'Analytics', 'Charts, trends & performance', 'chart charts graph report trends statistics'],
        ['members.index', 'group', 'Members', 'Member list & membership types', 'member membership regular student senior'],
        ['staff.index', 'assignment_ind', 'Staff Management', 'Staff, roles & shifts', 'staff employee trainer trainers front desk admin admins shift role'],
        ['checkins.index', 'settings', 'Check-in', 'Scan & log member check-ins', 'checkin check in attendance scan qr'],
        ['audit-logs', 'description', 'Audit Logs', 'Activity history', 'audit log logs history activity security'],
    ];
    $pages = array_map(fn ($item) => [
        'url' => route($item[0]), 'icon' => $item[1], 'title' => $item[2], 'subtitle' => $item[3], 'keywords' => $item[4],
    ], $nav);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · FitPass Gym</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=block">
    <link rel="stylesheet" href="{{ asset('css/fitpass.css') }}">
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('overview') }}">
            <img src="{{ asset('images/logo.png') }}" alt="" onerror="this.remove()">
            <span><b>FIT<em>PASS</em></b><small>GYM</small></span>
        </a>
        <nav>
            @foreach ($nav as $item)
                <a href="{{ route($item[0]) }}" @class(['active' => request()->routeIs($item[0])])>
                    <span class="material-symbols-outlined">{{ $item[1] }}</span>{{ $item[2] }}
                </a>
            @endforeach
        </nav>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="search">
                <span class="material-symbols-outlined">search</span>
                <input id="page-search" type="search" placeholder="Search pages..." autocomplete="off">
                <div id="search-results" class="results" hidden></div>
            </div>
            <div class="user">
                <span class="avatar"><span class="material-symbols-outlined">person</span></span>
                <div><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->role }}</small></div>
                <button class="icon-btn" title="Log out" data-open="logout-dialog"><span class="material-symbols-outlined">logout</span></button>
            </div>
        </header>

        <main class="content">@yield('content')</main>
    </div>
</div>

@include('partials.logout-dialog')

@if (session('status'))
    <div class="toast">{{ session('status') }}</div>
@endif

<script>
    const pages = {{ Js::from($pages) }};
    const input = document.getElementById('page-search');
    const results = document.getElementById('search-results');
    const matches = () => {
        const q = input.value.trim().toLowerCase();
        return q ? pages.filter(p => (p.title + ' ' + p.keywords).toLowerCase().includes(q)) : [];
    };

    // Header search: type to see matching pages, click one or press Enter to go there.
    input.addEventListener('input', () => {
        const found = matches();
        results.innerHTML = found.map(p =>
            `<a href="${p.url}"><span class="material-symbols-outlined">${p.icon}</span><span><b>${p.title}</b><small>${p.subtitle}</small></span></a>`
        ).join('');
        results.hidden = !found.length;
    });
    input.addEventListener('keydown', e => {
        if (e.key === 'Enter' && matches().length) location.href = matches()[0].url;
    });

    // A click on the dimmed backdrop lands on the dialog itself, outside its box.
    const outside = e => {
        const box = e.target.getBoundingClientRect();
        return e.clientX < box.left || e.clientX > box.right || e.clientY < box.top || e.clientY > box.bottom;
    };

    // Close dropdowns on outside click; open and close dialogs.
    document.addEventListener('click', e => {
        if (!e.target.closest('.search')) results.hidden = true;
        document.querySelectorAll('details.menu[open]').forEach(menu => menu.contains(e.target) || (menu.open = false));
        const opener = e.target.closest('[data-open]');
        if (opener) document.getElementById(opener.dataset.open).showModal();
        if (e.target.closest('[data-close]')) e.target.closest('dialog').close();
        if (e.target.matches('dialog.logout') && outside(e)) e.target.close();
    });

    // Forms with data-confirm ask before submitting.
    document.addEventListener('submit', e => {
        if (e.target.dataset.confirm && !confirm(e.target.dataset.confirm)) e.preventDefault();
    });

    @if ($errors->any())
        document.querySelector('dialog:not(.logout)')?.showModal();
    @endif
</script>
</body>
</html>