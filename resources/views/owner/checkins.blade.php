@extends('layouts.app')
@section('title', 'Check-in')

@section('content')
    <h1>Attendance</h1>
    @include('partials.cards')

    <section class="panel alt">
        <h2>Today's Attendance</h2>
        <div class="toolbar">
            @foreach (['All', 'QR-code', 'Manual', 'Currently In'] as $option)
                <a href="{{ route('checkins.index', ['filter' => $option]) }}" @class(['chip', 'active' => $filter === $option])>{{ $option }}</a>
            @endforeach
            <span class="spacer"></span>
            <a class="btn" href="{{ route('checkins.export') }}">Export</a>
        </div>

        <div class="table-wrap">
            <table>
                <tr><th>Member</th><th>Type</th><th>Time in</th><th>Time out</th><th>Method</th><th>Status</th></tr>
                @forelse ($logs as $log)
                    <tr>
                        <td><span class="who"><span class="avatar">{{ $log->initials }}</span>{{ $log->person_name }}</span></td>
                        <td>{{ $log->plan_label }}</td>
                        <td>{{ $log->time_in->format('g:i A') }}</td>
                        <td>{{ $log->time_out?->format('g:i A') ?? '----' }}</td>
                        <td><span @class(['badge', $log->method === 'Manual' ? 'blue' : 'green'])>{{ $log->method }}</span></td>
                        <td>
                            @if ($log->time_out)
                                <span class="badge grey">Checked Out</span>
                            @else
                                <span class="badge green">In Gym</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No check-ins to show.</td></tr>
                @endforelse
            </table>
        </div>

        @include('partials.pager', ['paginator' => $logs])
    </section>

@endsection