@extends('staff.layout')
@section('title', 'Payments')

@section('content')
    <h1>Payments</h1>

    <section class="panel alt">
        <h2>Payment Records</h2>
        <div class="toolbar">
            @foreach ($filters as $option)
                <a href="{{ route('staff.payments', ['filter' => $option]) }}" @class(['chip', 'active' => $filter === $option])>{{ $option }}</a>
            @endforeach
        </div>

        <div class="table-wrap">
            <table>
                <tr><th>Name</th><th>Date</th><th>Walk-in/Monthly</th><th>Amount</th><th>Method</th></tr>
                @forelse ($payments as $payment)
                    <tr>
                        <td>{{ $payment->name }}</td>
                        <td>{{ $payment->paid_on->format('M j, Y') }}</td>
                        <td>{{ $payment->kind }}</td>
                        <td>₱{{ number_format($payment->amount, 2) }}</td>
                        <td><span @class(['badge', $payment->method === 'Gcash' ? 'sky' : 'green'])>{{ $payment->method }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5">No payments found.</td></tr>
                @endforelse
            </table>
        </div>
    </section>
@endsection