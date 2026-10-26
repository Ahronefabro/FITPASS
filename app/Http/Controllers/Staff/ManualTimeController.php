<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\Concerns\RecordsAttendance;
use App\Models\CheckIn;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManualTimeController extends Controller
{
    use RecordsAttendance;

    public function index()
    {
        return view('staff.manual-time', [
            'entries' => CheckIn::logFor(today())->where('method', 'Manual')->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'member_id' => 'nullable|integer|exists:members,id',
            'guest_name' => 'required_without:member_id|nullable|string|max:255',
            'action' => ['required', Rule::in(['Check In', 'Check Out'])],
        ]);

        // A member on file is picked; walk-ins and first-timers have their name typed in by hand.
        $message = isset($data['member_id'])
            ? $this->logAttendance(Member::findOrFail($data['member_id']), $data['action'], 'Manual')
            : $this->logGuestAttendance(trim($data['guest_name']), $data['action'], 'Manual');

        return back()->with('status', $message);
    }
}
