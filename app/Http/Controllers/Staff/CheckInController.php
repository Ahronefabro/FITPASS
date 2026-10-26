<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\Concerns\RecordsAttendance;
use App\Models\CheckIn;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CheckInController extends Controller
{
    use RecordsAttendance;

    public const FILTERS = ['All', 'QR Code', 'Manual', 'Currently In'];

    public function index(Request $request)
    {
        $filter = $request->query('filter');
        if (! in_array($filter, self::FILTERS, true)) {
            $filter = 'All';
        }

        $log = CheckIn::logFor(today());
        $ins = $log->where('action', 'Check In')->count();
        $outs = $log->where('action', 'Check Out')->count();

        // One row per visit, so the time in and the time out sit side by side.
        $rows = CheckIn::with('member')
            ->where(fn ($query) => $query->whereDate('time_in', today())->orWhereDate('time_out', today()))
            ->when($filter === 'QR Code', fn ($query) => $query->where('method', 'QR Code'))
            ->when($filter === 'Manual', fn ($query) => $query->where('method', 'Manual'))
            // Arrivals that have not been closed out yet.
            ->when($filter === 'Currently In', fn ($query) => $query->whereNull('time_out'))
            ->latest('time_in')->get();

        return view('staff.checkins', [
            'filter' => $filter,
            'filters' => self::FILTERS,
            'rows' => $rows,
            'cards' => [
                ['Check-ins', $ins, 'Logged today'],
                ['Check-outs', $outs, 'Completed visits'],
                ['Currently In', CheckIn::whereNull('time_out')->count(), 'Still in the gym'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'member_id' => 'required|integer|exists:members,id',
            'action' => ['required', Rule::in(['Check In', 'Check Out'])],
        ]);

        $message = $this->logAttendance(Member::findOrFail($data['member_id']), $data['action'], 'Manual');

        return back()->with('status', $message);
    }
}
