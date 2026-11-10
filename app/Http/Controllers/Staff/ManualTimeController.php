<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\Concerns\RecordsAttendance;
use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ManualTimeController extends Controller
{
    use RecordsAttendance;

    // Walk-in fees in pesos. The amount is decided here on the server, never taken from the browser.
    private const WALK_IN_FEES = ['regular' => 100, 'student' => 80];

    public function index()
    {
        return view('staff.manual-time', [
            'entries' => CheckIn::logFor(today())->where('method', 'Manual')->values(),
            // Sent to the view so the prices on screen always match what the server charges
            'fees' => self::WALK_IN_FEES,
        ]);
    }

    // Past walk-in / first-timer names, for the search icon on the Manual Time page.
    // NOTE: assumes walk-ins are CheckIn rows with a null member_id and a guest_name column.
    public function search(Request $request)
    {
        $q = trim($request->query('q', ''));

        return CheckIn::query()
            ->whereNull('member_id')
            ->whereNotNull('guest_name')
            ->when($q !== '', fn ($query) => $query->where('guest_name', 'like', "%{$q}%"))
            ->selectRaw('guest_name as name, MAX(created_at) as last_visit')
            ->groupBy('guest_name')
            ->orderByRaw('MAX(created_at) desc')
            ->limit(20)
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'member_id' => 'nullable|integer|exists:members,id',
            'guest_name' => 'required_without:member_id|nullable|string|max:255',
            'action' => ['required', Rule::in(['Check In', 'Check Out'])],
            'walk_in_type' => ['nullable', Rule::in(array_keys(self::WALK_IN_FEES))],
            'payment_confirmed' => 'nullable|boolean',
            'payment_method' => ['nullable', Rule::in(Payment::METHODS)],
        ]);

        $isWalkInTimeIn = $data['action'] === 'Check In' && ! isset($data['member_id']);

        // A walk-in Time In must always come with a confirmed fee, so no walk-in
        // is ever let in without a payment record. Time Out needs no payment.
        if ($isWalkInTimeIn && (empty($data['payment_confirmed']) || empty($data['walk_in_type']))) {
            return back()->withInput()->withErrors([
                'guest_name' => 'Confirm the walk-in payment before recording a Time In.',
            ]);
        }

        $isWalkInPayment = $isWalkInTimeIn;
        $amount = $isWalkInPayment ? self::WALK_IN_FEES[$data['walk_in_type']] : null;
        $method = $data['payment_method'] ?? 'Cash';

        // Time in and payment are saved together, so one can't succeed without the other.
        $message = DB::transaction(function () use ($data, $isWalkInPayment, $amount, $method) {
            // A member on file is picked; walk-ins and first-timers have their name typed in by hand.
            $message = isset($data['member_id'])
                ? $this->logAttendance(Member::findOrFail($data['member_id']), $data['action'], 'Manual')
                : $this->logGuestAttendance(trim($data['guest_name']), $data['action'], 'Manual');

            if ($isWalkInPayment) {
                Payment::create([
                    'member_id' => null,
                    'name' => trim($data['guest_name']),
                    'paid_on' => today(),
                    'kind' => 'Walk-in',
                    'amount' => $amount,
                    'method' => $method,
                ]);
            }

            return $message;
        });

        $flash = ['status' => $message];

        if ($isWalkInPayment) {
            $flash['payment_confirmed'] = [
                'name' => trim($data['guest_name']),
                'type' => $data['walk_in_type'],
                'amount' => $amount,
                'method' => $method,
            ];
        }

        return back()->with($flash);
    }
}