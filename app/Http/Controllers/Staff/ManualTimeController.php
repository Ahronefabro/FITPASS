<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\Concerns\RecordsAttendance;
use App\Models\CheckIn;
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
            'guest_name' => 'required|string|max:255',
            'action' => ['required', Rule::in(['Check In', 'Check Out'])],
        ]);

        // This sheet is for walk-ins and first-timers, so the name is typed in by hand.
        $message = $this->logGuestAttendance(trim($data['guest_name']), $data['action'], 'Manual');

        return back()->with('status', $message);
    }
}
