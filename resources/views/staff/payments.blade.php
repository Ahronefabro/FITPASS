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
            <span class="spacer"></span>
            <button class="btn primary" data-open="record-payment">+ Record Payment</button>
        </div>

        <div class="table-wrap">
            <table>
                <tr><th>Name</th><th>Date</th><th>Walk-in/Monthly</th><th>Amount</th><th>Method</th></tr>
                @forelse ($payments as $payment)
                    <tr>
                        <td>{{ $payment->name }}</td>
                        <td>{{ $payment->paid_on->format('m-d-y') }}</td>
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

    <dialog id="record-payment" @if ($errors->any()) data-reopen @endif>
        <h2>RECORD PAYMENT</h2>
        <form method="POST" action="{{ route('staff.payments.store') }}">
            @csrf
            @if ($errors->any()) <p class="error">{{ $errors->first() }}</p> @endif

            <label>Name
                <input name="name" value="{{ old('name') }}" required>
            </label>
            <label>Date
                <input type="date" name="paid_on" value="{{ old('paid_on', today()->toDateString()) }}" required>
            </label>
            <label>Walk-in or monthly
                <select name="kind" required>
                    @foreach ($kinds as $kind)
                        <option value="{{ $kind }}" @selected(old('kind') === $kind)>{{ $kind }}</option>
                    @endforeach
                </select>
            </label>
            <label>Amount
                <input type="number" name="amount" step="0.01" min="0" value="{{ old('amount') }}" required>
            </label>
            <label>Method
                <select name="method" required>
                    @foreach ($methods as $method)
                        <option value="{{ $method }}" @selected(old('method') === $method)>{{ $method }}</option>
                    @endforeach
                </select>
            </label>

            <div class="actions">
                <button type="button" class="btn" data-close>Cancel</button>
                <button class="btn primary">RECORD</button>
            </div>
        </form>
    </dialog>
@endsection
