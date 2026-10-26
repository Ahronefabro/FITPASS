<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter');
        if (! in_array($filter, Payment::KINDS, true)) {
            $filter = 'All';
        }

        $payments = Payment::query()
            ->when($filter !== 'All', fn ($query) => $query->where('kind', $filter))
            ->latest('paid_on')->latest('id')
            ->simplePaginate(50)->withQueryString();

        return view('staff.payments', [
            'filter' => $filter,
            'filters' => array_merge(['All'], Payment::KINDS),
            'payments' => $payments,
            'kinds' => Payment::KINDS,
            'methods' => Payment::METHODS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'paid_on' => 'required|date',
            'kind' => ['required', Rule::in(Payment::KINDS)],
            'amount' => 'required|numeric|min:0',
            'method' => ['required', Rule::in(Payment::METHODS)],
        ]);

        $payment = Payment::create($data);
        AuditLog::record("recorded a {$payment->kind} payment from {$payment->name}");

        return back()->with('status', "Payment from {$payment->name} recorded");
    }
}
