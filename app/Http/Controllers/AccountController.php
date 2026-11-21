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

    /**
     * Username and email, saved together behind the current password. The
     * email is where "Forgot password?" sends the reset link; accounts made
     * from the starter data have made-up addresses, so this is where each
     * person puts in one they can actually open.
     */
    public function updateDetails(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validateWithBag('details', [
            'username' => [
                'required', 'string', 'min:3', 'max:50', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => ['required', 'current_password'],
        ]);

        $old = ['username' => $user->username, 'email' => $user->email];
        $user->fill([
            'username' => $data['username'],
            'email' => mb_strtolower(trim($data['email'])),
        ]);

        if (! $user->isDirty()) {
            return back()->with('status', 'Nothing to change.');
        }

        // In the form's order, for the message below.
        $changed = array_values(array_filter(['username', 'email'], fn ($field) => $user->isDirty($field)));
        $user->save();

        foreach ($changed as $field) {
            AuditLog::record("changed their {$field} from {$old[$field]} to {$user->{$field}}");
        }

        return back()->with('status', ucfirst(implode(' and ', $changed)).' updated.');
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