@extends('layouts.app')
@section('title', 'Audit Logs')

@section('content')
    <h1>Reports &amp; Audit Logs</h1>

    <section class="panel alt">
        <div class="toolbar">
            <a href="{{ route('audit-logs', ['tab' => 'reports']) }}" @class(['chip', 'active' => $tab === 'reports'])>Reports</a>
            <a href="{{ route('audit-logs') }}" @class(['chip', 'active' => $tab === 'logs'])>Audit Logs</a>
        </div>

        @if ($tab === 'reports')
            <div class="log"><span>Today's check-in log</span><a class="btn" href="{{ route('checkins.export') }}">Download CSV</a></div>
            <div class="log"><span>All check-ins</span><a class="btn" href="{{ route('checkins.export', ['all' => 1]) }}">Download CSV</a></div>
        @else
            @forelse ($logs as $log)
                <div class="log">
                    <time>{{ $log->created_at->isToday() ? 'Today '.$log->created_at->format('g:i A') : $log->created_at->format('M j, g:i A') }}</time>
                    <i class="dot"></i>
                    <span><b>{{ $log->user_name }}</b> {{ $log->action }}</span>
                </div>
            @empty
                <p class="grey">Nothing logged yet.</p>
            @endforelse

            @if ($logs->hasPages())
                <div class="toolbar">
                    @if ($logs->previousPageUrl()) <a class="chip" href="{{ $logs->previousPageUrl() }}">← Newer</a> @endif
                    <span class="spacer"></span>
                    @if ($logs->nextPageUrl()) <a class="chip" href="{{ $logs->nextPageUrl() }}">Older →</a> @endif
                </div>
            @endif
        @endif
    </section>
@endsection