@extends('layouts.app')
@section('title', 'Check-in')

@section('content')
    <h1>Check-ins</h1>
    @include('partials.cards')

    <section class="panel alt">
        <h2>Today's Check-in Logs</h2>
        <div class="toolbar">
            @foreach (['All', 'QR-code', 'Manual', 'Currently In'] as $option)
                <a href="{{ route('checkins.index', ['filter' => $option]) }}" @class(['chip', 'active' => $filter === $option])>{{ $option }}</a>
            @endforeach
            <span class="spacer"></span>
            <a class="btn" href="{{ route('checkins.export') }}">Export</a>
            <button class="btn primary" data-open="manual-entry">+ Manual Entry</button>
        </div>

        <div class="table-wrap">
            <table>
                <tr><th>Member</th><th>Title</th><th>Time in</th><th>Time out</th><th>Method</th><th>Status</th></tr>
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
                                <form class="inline" method="POST" action="{{ route('checkins.checkout', $log) }}">
                                    @csrf @method('PATCH')
                                    <span class="badge green">In Gym</span>
                                    <button class="btn small">Check out</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No check-ins to show.</td></tr>
                @endforelse
            </table>
        </div>
    </section>

    <dialog id="manual-entry">
        <h2>MANUAL ENTRY</h2>
        <form method="POST" action="{{ route('checkins.store') }}">
            @csrf
            @if ($errors->any()) <p class="error">{{ $errors->first() }}</p> @endif

            <label>Member
                <select name="member_id" required>
                    <option value="">Select a member</option>
                    @foreach ($members as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </label>

            <div class="actions">
                <button type="button" class="btn" data-close>Cancel</button>
                <button class="btn primary">Check in</button>
            </div>
        </form>
    </dialog>
@endsection