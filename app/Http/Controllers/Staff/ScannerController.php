<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\Concerns\RecordsAttendance;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScannerController extends Controller
{
    use RecordsAttendance;

    public function index()
    {
        return view('staff.scanner');
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
        $message = $this->logAttendance($member, $action, 'QR Code', 'code');

        return response()->json([
            'name' => mb_strtoupper($member->name),
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
