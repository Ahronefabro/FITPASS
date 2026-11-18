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

        $query = CheckIn::with('member');

        if ($filter === 'Currently In') {
            // Every open visit, any date. Matches the "Currently In" card.
            $query->whereNull('time_out');
        } else {
            $query->where(fn ($q) => $q->whereDate('time_in', today())->orWhereDate('time_out', today()))
                ->when($filter === 'QR Code', fn ($q) => $q->where('method', 'QR Code'))
                ->when($filter === 'Manual', fn ($q) => $q->where('method', 'Manual'));
        }

        $rows = $query->latest('time_in')->get();

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