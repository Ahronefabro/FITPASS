<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Forgotten passwords. Someone asks for a link by the email on their account,
 * and the link opens a form where they choose a new password. Works the same
 * for every role, like the sign-in it sits beside.
 */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (Throwable $e) {
            // Usually the mail settings in .env: a wrong app password or no internet.
            report($e);

            return back()->withErrors(['email' => 'The email could not be sent right now. Try again later.']);
        }

        // The same answer whether or not the address has an account, so the
        // form cannot be used to find out who works here.
        return back()->with('status', 'If that email belongs to an account, a reset link is on its way.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                // A new remember token signs out any "remember me" session that
                // may have been opened with the old password.
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                AuditLog::record('reset their password', $user->name);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'This reset link is invalid or has expired. Ask for a new one.',
            ]);
        }

        return redirect()->route('login')->with('status', 'Your password has been reset. You can log in now.');
    }
}
