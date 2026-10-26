@php
    $nav = [
        ['staff.dashboard', 'dashboard_customize', 'Dashboard'],
        ['staff.checkins', 'login', 'Check-ins'],
        ['staff.payments', 'payments', 'Payments'],
        ['staff.members', 'group', 'Members'],
        ['staff.manual-time', 'schedule', 'Manual Time in/out'],
        ['staff.notifications', 'notifications', 'Notifications'],
        ['staff.scanner', 'qr_code_scanner', 'Scanner'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · FitPass Staff</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=block">
    <link rel="stylesheet" href="{{ asset('css/fitpass.css') }}">
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('staff.dashboard') }}">
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
            <details class="menu user-menu">
                <summary class="user">
                    <span class="avatar"><span class="material-symbols-outlined">person</span></span>
                    <div><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->role }}</small></div>
                    <span class="material-symbols-outlined">expand_more</span>
                </summary>
                <div class="user-dropdown">
                    <a href="{{ route('account.edit') }}">
                        <span class="material-symbols-outlined">manage_accounts</span>Account Settings
                    </a>
                    <button type="button" data-open="logout-dialog">
                        <span class="material-symbols-outlined">logout</span>Log out
                    </button>
                </div>
            </details>
        </header>

        <main class="content">@yield('content')</main>
    </div>
</div>

@include('partials.logout-dialog')

@if (session('status'))
    <div class="toast">{{ session('status') }}</div>
@endif

<script>
    // A click on the dimmed backdrop lands on the dialog itself, outside its box.
    const outside = e => {
        const box = e.target.getBoundingClientRect();
        return e.clientX < box.left || e.clientX > box.right || e.clientY < box.top || e.clientY > box.bottom;
    };

    // Close dropdowns on outside click; open and close dialogs.
    document.addEventListener('click', e => {
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
        // Reopen the dialog the failed form came from, so the errors are visible.
        (document.querySelector('dialog[data-reopen]') ?? document.querySelector('dialog:not(.logout)'))?.showModal();
    @endif
</script>
@stack('scripts')
</body>
</html>