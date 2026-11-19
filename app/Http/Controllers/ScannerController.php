<?php

namespace App\Http\Controllers;

use App\Auth\Portal;
use App\Http\Controllers\Staff\Concerns\RecordsAttendance;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The member QR scanner. It lives on the front page only, open without signing
 * in, so the gate tablet just loads fitpass.site. Each scan lands on the same
 * check-ins and audit log the staff and owner portals read.
 */
class ScannerController extends Controller
{
    use RecordsAttendance;

    public function index(): View
    {
        $portal = Portal::forRole(auth()->user()?->role);

        return view('scanner', [
            // Signed in: back to their own portal. Otherwise: the sign-in.
            'backUrl' => $portal ? route(Portal::homeRoute($portal)) : route('login'),
            'backLabel' => match ($portal) {
                Portal::OWNER => 'Owner portal',
                Portal::STAFF => 'Staff portal',
                default => 'Staff / Owner login',
            },
            'backIcon' => $portal ? 'arrow_back' : 'login',
        ]);
    }

    /**
     * A scan records straight away, no confirm step: the first scan checks the
     * member in, the next one checks them out. Answers with the member card
     * and what was recorded.
     */
    public function store(Request $request)
    {
        $member = $this->find($request);
        $action = $member->openCheckIn() ? 'Check Out' : 'Check In';

        // An expired member is turned away at the door. One already inside
        // can still be checked out, so no visit is left open.
        if ($action === 'Check In' && $member->status === 'Expired') {
            throw ValidationException::withMessages([
                'code' => "Membership expired on {$member->expires_at->format('F j, Y')}. {$member->name} must renew first.",
            ]);
        }

        $message = $this->logAttendance($member, $action, 'QR Code', 'code');

        return response()->json([
            'name' => mb_strtoupper($member->name),
            // Lets staff see that the face matches the code.
            'photo' => $member->photo_url,
            'meta' => $member->plan.' Member  •  ID #'.str_pad((string) $member->id, 4, '0', STR_PAD_LEFT),
            'time' => now()->format('g:i A'),
            'method' => 'QR CODE',
            'membership' => mb_strtoupper($member->status),
            'visits' => (string) $member->visits_this_month,
            'action' => $action,
            'message' => $message,
        ]);
    }

    private function find(Request $request): Member
    {
        $data = $request->validate(['code' => 'required|string|max:64']);

        // Codes are printed in capitals; a hand-typed one may not be.
        $member = Member::where('qr_code', mb_strtoupper(trim($data['code'])))->first();

        if (! $member) {
            throw ValidationException::withMessages(['code' => 'QR code not recognized.']);
        }

        return $member;
    }
}
