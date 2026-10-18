<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
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
}
