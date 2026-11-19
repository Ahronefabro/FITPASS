{{-- The FitPass logo as the site's icon: browser tab, bookmarks, and the
     home screen when a phone saves the page. Built from images/logo.png at
     its original proportions. --}}
<link rel="icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}" sizes="16x16 32x32 48x48">
<link rel="icon" type="image/png" href="{{ asset('images/icon-512.png') }}" sizes="512x512">
<link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
<meta name="theme-color" content="#1B1B1B">
