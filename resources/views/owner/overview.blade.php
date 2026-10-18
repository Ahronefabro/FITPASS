@extends('layouts.app')
@section('title', 'Overview')

@section('content')
    <h1>Dashboard</h1>
    @include('partials.cards')

    <div class="grid g-2-3">
        <section class="panel">
            <h2>Recent Check-ins</h2>
            <table class="plain">
                <tr><th>Name</th><th>Type</th><th>Time</th><th>Method</th></tr>
                @forelse ($recent as $log)
                    <tr>
                        <td>{{ $log->person_name }}</td>
                        <td>{{ $log->plan_label }}</td>
                        <td>{{ $log->time_in->format('M j, g:i A') }}</td>
                        <td>{{ $log->method }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No check-ins yet.</td></tr>
                @endforelse
            </table>
            <p class="center"><a class="link" href="{{ route('checkins.index') }}">View All</a></p>
        </section>

        <section class="panel">
            <div class="panel-head">
                <h2>Check-ins Overview</h2>
                <form>
                    <select name="range" onchange="this.form.submit()">
                        @foreach ($ranges as $key => $label)
                            <option value="{{ $key }}" @selected($range === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            @php $max = max($chart->max(), 1); @endphp
            <div class="bars">
                @foreach ($chart as $label => $value)
                    <div><small>{{ $value }}</small><i style="height:{{ $value / $max * 100 }}%"></i><span>{{ $label }}</span></div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="grid g-3">
        <section class="panel">
            <h2>Top Performing Days</h2>
            @forelse ($topDays as $day => $total)
                <div class="row"><span>{{ $loop->iteration }}. {{ \Illuminate\Support\Carbon::parse($day)->format('M j, Y') }}</span><b>{{ $total }}</b></div>
            @empty
                <div class="row">No check-ins yet.</div>
            @endforelse
        </section>

        <section class="panel">
            <h2>Members by Type</h2>
            <div class="donut" style="background:{{ $donut }}"></div>
            @foreach ($byPlan as $plan => $percent)
                <div class="legend"><i style="background:{{ $colours[$plan] }}"></i>{{ $plan }}<b>{{ $percent }}%</b></div>
            @endforeach
        </section>

        <section class="panel">
            <h2>System Status</h2>
            <div class="row"><span>Database</span><b class="green">Online</b></div>
            @foreach (['QR Scanner', 'Payment Gateway', 'Backup'] as $system)
                <div class="row"><span>{{ $system }}</span><span class="grey">Not connected</span></div>
            @endforeach
        </section>
    </div>
@endsection