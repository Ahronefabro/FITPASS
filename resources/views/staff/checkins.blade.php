@extends('staff.layout')
@section('title', 'Check-ins')

@section('content')
    <h1>Check-ins</h1>
    @include('partials.cards')

    <section class="panel alt">
        <h2>Today's Check-in Logs</h2>
        <div class="toolbar">
            @foreach ($filters as $option)
                <a href="{{ route('staff.checkins', ['filter' => $option]) }}" @class(['chip', 'active' => $filter === $option])>{{ $option }}</a>
            @endforeach
        </div>

        <div class="table-wrap">
            <table>
                <tr><th>Name</th><th>Checked in</th><th>Checked out</th><th>Method</th><th>Status</th></tr>
                @forelse ($rows as $visit)
                    <tr>
                        <td><span class="who"><span class="avatar">{{ $visit->initials }}</span>{{ $visit->person_name }}</span></td>
                        {{-- A visit left open overnight is closed out today, so its arrival needs the date. --}}
                        <td class="green">{{ $visit->time_in->format($visit->time_in->isToday() ? 'g:i A' : 'M j, g:i A') }}</td>
                        <td @class(['orange' => $visit->time_out])>{{ $visit->time_out?->format('g:i A') ?? '----' }}</td>
                        <td><span @class(['badge', $visit->method === 'Manual' ? 'blue' : 'green'])>{{ $visit->method }}</span></td>
                        <td>
                            @if ($visit->time_out)
                                <span class="badge grey">Checked Out</span>
                            @else
                                <span class="badge green">Still In</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No logs found.</td></tr>
                @endforelse
            </table>
        </div>
    </section>
@endsection
