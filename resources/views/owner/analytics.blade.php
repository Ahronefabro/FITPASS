@extends('layouts.app')
@section('title', 'Analytics')

@section('content')
    <h1>Analytics</h1>
    @include('partials.cards')

    <div class="grid g-3-2">
        <section class="panel">
            <h2>Check-ins: Last 7 Days</h2>
            @php $max = max($week->max(), 1); @endphp
            <div class="bars">
                @foreach ($week as $label => $value)
                    <div><small>{{ $value }}</small><i style="height:{{ $value / $max * 100 }}%"></i><span>{{ $label }}</span></div>
                @endforeach
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <h2>Revenue by Plan</h2>
                <small>₱{{ number_format($revenueTotal) }} active</small>
            </div>
            <div class="segments">
                @foreach ($revenue as $plan => $percent)
                    <i style="width:{{ $percent }}%;background:{{ $colours[$plan] }}"></i>
                @endforeach
            </div>
            @foreach ($revenue as $plan => $percent)
                <div class="legend"><i style="background:{{ $colours[$plan] }}"></i>{{ $plan }}<b>{{ $percent }}%</b></div>
            @endforeach
        </section>
    </div>

    <div class="grid g-3-2">
        <section class="panel">
            <h2>Peak Hours <small>last 30 days</small></h2>
            @forelse ($peak as $row)
                <div class="row">
                    <span class="time">{{ $row['time'] }}</span>
                    <span class="meter"><i @class(['peak' => $row['label'] === 'Peak']) style="width:{{ $row['level'] * 100 }}%"></i></span>
                    <span class="tag">{{ $row['label'] }}</span>
                </div>
            @empty
                <div class="row">No check-ins in the last 30 days.</div>
            @endforelse
        </section>

        <section class="panel">
            <h2>Member Growth (6 Months)</h2>
            <div class="spark">
                <svg viewBox="0 0 100 100" preserveAspectRatio="none">
                    <polyline points="{{ $points->map(fn ($p) => implode(',', $p))->implode(' ') }}"/>
                </svg>
                @foreach ($points as $p)
                    <i style="left:{{ $p[0] }}%;top:{{ $p[1] }}%"></i>
                @endforeach
            </div>
            <div class="spark-labels">
                @foreach ($growth as $month => $count)
                    <span>{{ $month }}<br>{{ $count }}</span>
                @endforeach
            </div>
        </section>
    </div>
@endsection