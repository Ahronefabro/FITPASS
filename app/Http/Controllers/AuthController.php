<?php

namespace App\Http\Controllers;

use App\Auth\Portal;
use App\Models\AuditLog;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The one sign-in for the whole system. Everyone uses the same form, and the
 * role on their user record decides which portal opens.
 */
class AuthController extends Controller
{
    public function show(): View
    {
        return view('login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Check the password and the role before signing anyone in, so an
        // account that belongs to no portal never holds a session at all. One
        // message for every failure keeps the form from confirming which names
        // exist or which role an account carries.
        $user = Auth::validate($credentials) ? Auth::getLastAttempted() : null;
        $portal = Portal::forRole($user?->role);

        if ($user === null || $portal === null) {
            throw ValidationException::withMessages([
                'username' => 'Wrong name or password.',
            ]);
        }

        // Only told after the password checks out, so it confirms nothing to a
        // stranger guessing names.
        if ($user->isDeactivated()) {
            throw ValidationException::withMessages([
                'username' => 'This account has been deactivated. Please contact the owner.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        AuditLog::record("signed in to the {$portal} portal");

        // Keep the owner's staff directory in step with who actually signed in.
        Staff::where('name', $user->name)->update(['last_login_at' => now()]);

        return redirect()->to($this->landingUrl($request, $user, $portal));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * The role decides where a sign-in lands. A page someone was heading for is
     * only honoured when their role admits them to it, so a front-desk bookmark
     * of an owner page does not bounce them straight back here.
     */
    private function landingUrl(Request $request, User $user, string $portal): string
    {
        $intended = $request->session()->pull('url.intended');
        $home = route(Portal::homeRoute($portal));

        if (! is_string($intended) || $intended === '') {
            return $home;
        }

        $target = Portal::forUrl($intended);

        if ($target === null || ! Portal::allows($target, $user->role)) {
            return $home;
        }

        // Rebuilt from the path rather than echoed back, so the redirect can
        // only ever land inside this app.
        return url(parse_url($intended, PHP_URL_PATH)).
            (($query = parse_url($intended, PHP_URL_QUERY)) ? '?'.$query : '');
    }
}
