@extends($layout)
@section('title', 'My Account')

@section('content')
    <h1>My Account</h1>

    <div class="grid g-2">
        <section class="panel">
            <h2>Email</h2>

            @if ($pendingEmail)
                <div class="verify-box">
                    <span class="material-symbols-outlined">mark_email_unread</span>
                    <p>We sent a 6-digit code to <b>{{ $pendingEmail }}</b>. Enter it to finish changing your email.</p>

                    <form method="POST" action="{{ route('account.email.verify') }}">
                        @csrf
                        @if ($errors->verify->any()) <p class="error">{{ $errors->verify->first() }}</p> @endif
                        <input name="code" class="code-entry" inputmode="numeric" autocomplete="one-time-code"
                            maxlength="7" placeholder="000000" required autofocus>
                        <button class="btn primary wide">VERIFY EMAIL</button>
                    </form>

                    <div class="verify-links">
                        <form method="POST" action="{{ route('account.email.resend') }}">
                            @csrf
                            <button class="link">Send a new code</button>
                        </form>
                        <form method="POST" action="{{ route('account.email.cancel') }}">
                            @csrf
                            @method('DELETE')
                            <button class="link">Cancel</button>
                        </form>
                    </div>
                </div>
            @else
                @if ($errors->verify->any()) <p class="error">{{ $errors->verify->first() }}</p> @endif
            @endif

            <form method="POST" action="{{ route('account.email') }}" @if ($pendingEmail) hidden @endif>
                @csrf
                @method('PUT')
                @if ($errors->email->any()) <p class="error">{{ $errors->email->first() }}</p> @endif

                <label>Email <small>password reset links are sent here</small>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                </label>
                <label>Current password
                    <input type="password" name="current_password" required autocomplete="current-password">
                </label>

                <button class="btn primary wide">SEND VERIFICATION CODE</button>
            </form>
        </section>

        <section class="panel">
            <h2>Password</h2>
            <form method="POST" action="{{ route('account.password') }}">
                @csrf
                @method('PUT')
                @if ($errors->password->any()) <p class="error">{{ $errors->password->first() }}</p> @endif

                <label>Current password
                    <input type="password" name="current_password" required autocomplete="current-password">
                </label>
                <label>New password <small>at least 8 characters</small>
                    <input type="password" name="password" required autocomplete="new-password">
                </label>
                <label>Confirm new password
                    <input type="password" name="password_confirmation" required autocomplete="new-password">
                </label>

                <button class="btn primary wide">CHANGE PASSWORD</button>
            </form>
        </section>
    </div>

    <section class="panel">
        <h2>Sign-in</h2>
        <p>Username: <b>{{ $user->username }}</b> · Role: <b>{{ $user->role }}</b></p>
    </section>
@endsection
