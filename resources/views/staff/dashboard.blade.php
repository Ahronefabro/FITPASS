@extends('staff.layout')
@section('title', 'Dashboard')

@section('content')
    <h1>Dashboard</h1>
    @include('partials.cards')

    <div class="grid g-2-3">
        <section class="panel">
            <h2>Recent Check-ins</h2>
            <table class="plain">
                <tr><th>Name</th><th>Type</th><th>Time</th><th>Method</th></tr>
                @forelse ($recent as $event)
                    <tr>
                        <td>{{ $event['row']->person_name }}</td>
                        <td>{{ $event['row']->plan_label }}</td>
                        <td>{{ $event['at']->format('g:i A') }}</td>
                        <td>{{ $event['method'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="center grey">No recent check-ins</td></tr>
                @endforelse
            </table>
            <p class="center"><a class="link" href="{{ route('staff.checkins') }}">View All</a></p>
        </section>

        <section class="panel">
            <div class="panel-head">
                <h2>Check-ins Overview</h2>
                <form method="GET" action="{{ route('staff.dashboard') }}">
                    <select name="range" onchange="this.form.submit()">
                        @foreach ($ranges as $key => $label)
                            <option value="{{ $key }}" @selected($range === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="spark">
                <svg viewBox="0 0 100 100" preserveAspectRatio="none">
                    <polyline points="{{ $inPoints }}"></polyline>
                    <polyline class="alt" points="{{ $outPoints }}"></polyline>
                </svg>
            </div>
            <div class="spark-labels">
                @foreach ($labels as $label)
                    <span>{{ $label }}</span>
                @endforeach
            </div>
            <div class="chart-key">
                <span><i></i>Check-ins</span>
                <span><i class="alt"></i>Check-outs</span>
                <small>Peak {{ $peak }}</small>
            </div>
        </section>
    </div>

    <section class="panel">
        <div class="panel-head">
            <h2>Today's Logs</h2>
            <a class="link" href="{{ route('staff.checkins') }}">View All</a>
        </div>
        @foreach ($totals as $title => $value)
            <div class="row"><span>{{ $title }}</span><b>{{ $value }}</b></div>
        @endforeach
    </section>
@endsection
