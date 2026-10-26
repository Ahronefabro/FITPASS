<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        // The phone sends its times with an offset; visits are stored in the app's timezone.
        $timezone = config('app.timezone');
        $visits = $request->user()->checkIns()
            ->whereBetween('time_in', [
                $request->date('from')->setTimezone($timezone),
                $request->date('to')->setTimezone($timezone),
            ])
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

    public function updatePhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'photo.max' => 'The picture must be smaller than 5 MB.',
        ]);

        $member = $request->user();
        $member->replacePhoto($request->file('photo'));

        return response()->json(['member' => $member->toApp()]);
    }

    public function destroyPhoto(Request $request): JsonResponse
    {
        $member = $request->user();
        $member->removePhoto();

        return response()->json(['member' => $member->toApp()]);
    }

    /**
     * A profile picture, by its random file name. No login: the app shows it
     * as a plain image and staff screens show it too.
     */
    public function photo(string $file): StreamedResponse
    {
        $path = Member::PHOTO_DIR.'/'.$file;
        abort_unless(Storage::disk('local')->exists($path), 404);

        // A new picture always gets a new name, so a copy never goes stale.
        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
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
