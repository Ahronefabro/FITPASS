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

    /** Turn a scanned code into the member card the page shows. */
    public function lookup(Request $request)
    {
        $member = $this->find($request);
        $action = $member->openCheckIn() ? 'Check Out' : 'Check In';

        return response()->json([
            'name' => mb_strtoupper($member->name),
            'meta' => $member->plan.' Member  •  ID #'.str_pad((string) $member->id, 4, '0', STR_PAD_LEFT),
            'time' => now()->format('g:i A'),
            'method' => 'QR CODE',
            'membership' => mb_strtoupper($member->status),
            'visits' => (string) $member->visits_this_month,
            'action' => $action,
        ]);
    }

    /** Confirm the scan: the next event is whichever one the member is due. */
    public function store(Request $request)
    {
        $member = $this->find($request);
        $action = $member->openCheckIn() ? 'Check Out' : 'Check In';

        return response()->json([
            'message' => $this->logAttendance($member, $action, 'QR Code', 'code'),
        ]);
    }

    private function find(Request $request): Member
    {
        $data = $request->validate(['code' => 'required|string|max:64']);

        $member = Member::where('qr_code', $data['code'])->first();

        if (! $member) {
            throw ValidationException::withMessages(['code' => 'QR code not recognized.']);
        }

        return $member;
    }
}
