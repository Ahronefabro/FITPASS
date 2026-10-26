<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Member;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The form a member opens from the reset email the app asked for. Members only
 * log in on the app, so once the password is changed this page sends them back
 * there instead of to the staff sign-in.
 */
class MemberPasswordResetController extends Controller
{
    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
            'action' => route('member.password.update'),
            'member' => true,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker('members')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Member $member, string $password) {
                $member->update(['password' => $password]);

                // Every phone that was logged in with the old password logs in again.
                $member->tokens()->delete();

                event(new PasswordReset($member));
                AuditLog::record('reset their app password', $member->name);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'This reset link is invalid or has expired. Ask for a new one in the app.',
            ]);
        }

        return back()->with('status', 'Your password has been reset. You can log in on the FitPass app now.');
    }
}
