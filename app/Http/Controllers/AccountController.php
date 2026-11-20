<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(): View
    {
        return view('account.settings');
    }

    public function updateUsername(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validateWithBag('username', [
            'username' => [
                'required', 'string', 'min:3', 'max:50', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'current_password' => ['required', 'current_password'],
        ]);

        $old = $user->username;
        $user->update(['username' => $data['username']]);

        AuditLog::record("changed their username from {$old} to {$user->username}");

        return back()->with('status', 'Username updated.');
    }

    /**
     * Where "Forgot password?" sends the reset link. Accounts made from the
     * starter data have made-up addresses, so this is how each person puts in
     * one they can actually open.
     */
    public function updateEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validateWithBag('email', [
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => ['required', 'current_password'],
        ]);

        $old = $user->email;
        $user->update(['email' => mb_strtolower(trim($data['email']))]);

        AuditLog::record("changed their email from {$old} to {$user->email}");

        return back()->with('status', 'Email updated. Password reset links now go to '.$user->email.'.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ]);

        // The model's 'hashed' cast hashes this automatically.
        $request->user()->update(['password' => $data['password']]);

        // Fresh session id after a credential change; the user stays signed in.
        $request->session()->regenerate();

        AuditLog::record('changed their password');

        return back()->with('status', 'Password updated.');
    }
}