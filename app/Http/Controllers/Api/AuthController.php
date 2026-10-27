<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Plan;
use App\Models\StaffNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Accounts for the mobile app. Anyone can sign up there; the membership stays
 * pending until staff take the first payment at the front desk.
 */
class AuthController extends Controller
{
    /** The plans a new member can pick at sign-up, with what each costs. */
    public function plans(): JsonResponse
    {
        return response()->json([
            'plans' => Plan::orderBy('id')->get(['name', 'price'])
                ->map(fn (Plan $plan) => ['name' => $plan->name, 'price' => (float) $plan->price]),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('members', 'email')],
            'contact' => 'nullable|string|max:32',
            'plan' => ['required', Rule::exists('plans', 'name')],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $member = Member::create($data + [
            'membership_type' => 'Monthly Unlimited',
            'amount' => Plan::priceOf($data['plan']),
        ]);

        AuditLog::record('signed up in the app', $member->name);
        StaffNotification::create([
            'title' => 'New app sign-up',
            'body' => "{$member->name} signed up as {$member->plan} and is waiting for payment.",
            'kind' => 'Manual',
            'level' => 'info',
        ]);

        return response()->json([
            'message' => 'Account created. Pay at the front desk to activate your membership.',
            'member' => $member->toApp(),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        $member = Member::withPending()->where('email', $data['email'])->first();

        if (! $member || ! $member->password || ! Hash::check($data['password'], $member->password)) {
            throw ValidationException::withMessages(['email' => 'Wrong email or password.']);
        }

        return response()->json([
            'token' => $member->createToken($data['device_name'] ?? 'mobile')->plainTextToken,
            'member' => $member->toApp(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        try {
            Password::broker('members')->sendResetLink($request->only('email'));
        } catch (Throwable $e) {
            // Usually the mail settings in .env: a wrong app password or no internet.
            report($e);

            return response()->json(['message' => 'The email could not be sent right now. Try again later.'], 503);
        }

        // The same answer whether or not the address has an account.
        return response()->json(['message' => 'If that email belongs to an account, a reset link is on its way.']);
    }
}
