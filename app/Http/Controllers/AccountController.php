<?php

namespace App\Http\Controllers;

use App\Auth\Portal;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\EmailChangeCode;
use App\Notifications\EmailChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Whoever is signed in can change their own email and password here. Both ask
 * for the current password, so someone at an unattended desk cannot take the
 * account over by pointing its email somewhere else and resetting it.
 *
 * A new email only takes effect once a code sent to it has been typed back,
 * since password reset links go there and a mistyped address would lock the
 * person out.
 */
class AccountController extends Controller
{
    /** Session key for an email change waiting on its code. */
    private const PENDING = 'account.pending_email';

    private const CODE_MINUTES = 15;

    private const MAX_TRIES = 5;

    public function edit(Request $request): View
    {
        $pending = $this->pending($request);

        return view('account', [
            'user' => $request->user(),
            'pendingEmail' => $pending['email'] ?? null,
            // The page opens inside the portal the account signs in to.
            'layout' => Portal::forRole($request->user()->role) === Portal::STAFF ? 'staff.layout' : 'layouts.app',
        ]);
    }

    /** Step one: check the password, then send a code to the new address. */
    public function updateEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validateWithBag('email', [
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => 'required|current_password',
        ]);

        if (strcasecmp($data['email'], (string) $user->email) === 0) {
            throw ValidationException::withMessages(['email' => 'That is already your email.'])->errorBag('email');
        }

        $this->sendCode($request, $data['email']);

        return back()->with('status', "We sent a 6-digit code to {$data['email']}");
    }

    /** Step two: the code from the new inbox makes the change. */
    public function verifyEmail(Request $request): RedirectResponse
    {
        $request->validateWithBag('verify', ['code' => 'required|string']);

        $user = $request->user();
        $pending = $this->pending($request);
        $fail = fn (string $message) => ValidationException::withMessages(['code' => $message])->errorBag('verify');

        if (! $pending) {
            $request->session()->forget(self::PENDING);
            throw $fail('That code has expired. Enter your new email again to get a new one.');
        }

        if (! Hash::check(preg_replace('/\D/', '', $request->input('code')), $pending['code'])) {
            $pending['tries']++;

            if ($pending['tries'] >= self::MAX_TRIES) {
                $request->session()->forget(self::PENDING);
                throw $fail('Too many wrong codes. Enter your new email again to get a new one.');
            }

            $request->session()->put(self::PENDING, $pending);
            throw $fail('That code is not right. Check the email and try again.');
        }

        // Someone else may have taken the address while the code was in transit.
        if (User::where('email', $pending['email'])->whereKeyNot($user->id)->exists()) {
            $request->session()->forget(self::PENDING);
            throw $fail('That email is now used by another account.');
        }

        $old = $user->email;
        $user->forceFill(['email' => $pending['email'], 'email_verified_at' => now()])->save();
        $request->session()->forget(self::PENDING);

        if ($old) {
            Notification::route('mail', $old)->notify(new EmailChanged($user->username, $user->email));
        }

        AuditLog::record('changed their account email');

        return back()->with('status', 'Your email was verified and updated');
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::PENDING);

        if (! $pending) {
            return back();
        }

        $this->sendCode($request, $pending['email']);

        return back()->with('status', "We sent a new code to {$pending['email']}");
    }

    public function cancelEmail(Request $request): RedirectResponse
    {
        $request->session()->forget(self::PENDING);

        return back()->with('status', 'Email change cancelled');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validateWithBag('password', [
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->forceFill([
            'password' => $data['password'],
            'remember_token' => Str::random(60),
        ])->save();
        AuditLog::record('changed their password');

        return back()->with('status', 'Your password was changed');
    }

    private function sendCode(Request $request, string $email): void
    {
        $code = (string) random_int(100000, 999999);

        // Only a hash is kept, and only in this person's own session.
        $request->session()->put(self::PENDING, [
            'email' => $email,
            'code' => Hash::make($code),
            'expires' => now()->addMinutes(self::CODE_MINUTES)->getTimestamp(),
            'tries' => 0,
        ]);

        try {
            Notification::route('mail', $email)->notify(new EmailChangeCode($code, self::CODE_MINUTES));
        } catch (Throwable $e) {
            // Usually the mail settings in .env: a wrong app password or no internet.
            report($e);
            $request->session()->forget(self::PENDING);

            throw ValidationException::withMessages([
                'email' => 'The code could not be sent. Check the mail settings and try again.',
            ])->errorBag('email');
        }
    }

    /** The waiting change, or null when there is none or its code has expired. */
    private function pending(Request $request): ?array
    {
        $pending = $request->session()->get(self::PENDING);

        return $pending && $pending['expires'] > now()->getTimestamp() ? $pending : null;
    }
}
