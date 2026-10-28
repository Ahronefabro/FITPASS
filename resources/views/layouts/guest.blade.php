<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · FitPass Gym</title>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=block">
    <link rel="stylesheet" href="{{ asset('css/fitpass.css') }}">
</head>
<body class="login">
    <div class="login-brand">
        <div>
            <div class="logo"><img src="{{ asset('images/logo.png') }}" alt="FitPass Gym" onerror="this.remove()"></div>
            <div class="wordmark"><b>FIT<em>PASS</em></b><small>GYM</small></div>
        </div>
    </div>

    <div class="login-form">
        @yield('form')
    </div>
</body>
</html>
