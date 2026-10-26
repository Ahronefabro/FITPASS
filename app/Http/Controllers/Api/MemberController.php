<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password as PasswordRule;

/** What a logged-in member sees about themselves in the app. */
class MemberController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['member' => $request->user()->toApp()]);
    }

    /** Visits whose time in falls in the range, newest first. */
    public function checkIns(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        // The phone sends its own local times with an offset; visits are stored in UTC.
        $visits = $request->user()->checkIns()
            ->whereBetween('time_in', [$request->date('from')->utc(), $request->date('to')->utc()])
            ->latest('time_in')
            ->limit(500)
            ->get();

        return response()->json(['check_ins' => $visits->map(fn (CheckIn $visit) => $this->visit($visit))]);
    }

    public function latestCheckIn(Request $request): JsonResponse
    {
        $visit = $request->user()->checkIns()->latest('time_in')->first();

        return response()->json(['check_in' => $visit ? $this->visit($visit) : null]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string|current_password:sanctum',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $member = $request->user();
        $member->update(['password' => $data['password']]);

        // Sign out other phones that knew the old password.
        $member->tokens()->whereKeyNot($member->currentAccessToken()->getKey())->delete();

        return response()->json(['message' => 'Password updated.']);
    }

    private function visit(CheckIn $visit): array
    {
        return [
            'id' => $visit->id,
            'time_in' => $visit->time_in->toIso8601String(),
            'time_out' => $visit->time_out?->toIso8601String(),
            'method' => $visit->method,
        ];
    }
}
