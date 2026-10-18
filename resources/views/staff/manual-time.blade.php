@extends('staff.layout')
@section('title', 'Manual Time in/out')

@section('content')
    <h1>Manual Time in / out</h1>

    <section class="panel">
        <h2>Manual Time Entry</h2>
        <form method="POST" action="{{ route('staff.manual-time.store') }}">
            @csrf
            @if ($errors->any()) <p class="error">{{ $errors->first() }}</p> @endif

            <label>Name <small>walk-in or first-timer</small>
                <input name="guest_name" value="{{ old('guest_name') }}" placeholder="Type the name" required>
            </label>

            <div class="seg">
                @foreach (['Check In' => 'Time In', 'Check Out' => 'Time Out'] as $value => $label)
                    <label>
                        <input type="radio" name="action" value="{{ $value }}" @checked(old('action', 'Check In') === $value)>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <button class="btn primary wide">RECORD TIME</button>
        </form>
    </section>

    <section class="panel">
        <h2>Recent Manual Entries <small>today</small></h2>
        <div class="table-wrap">
            <table>
                <tr><th>Name</th><th>Action</th><th>Time</th></tr>
                @forelse ($entries as $event)
                    <tr>
                        <td><span class="who"><span class="avatar">{{ $event['row']->initials }}</span>{{ $event['row']->person_name }}</span></td>
                        <td><b @class([$event['action'] === 'Check In' ? 'green' : 'orange'])>{{ $event['action'] }}</b></td>
                        <td>{{ $event['at']->format('g:i A') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No manual entries yet.</td></tr>
                @endforelse
            </table>
        </div>
    </section>
@endsection
