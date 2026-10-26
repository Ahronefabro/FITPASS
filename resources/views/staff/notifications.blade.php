@extends('staff.layout')
@section('title', 'Notifications')

@section('content')
    <h1>Notifications</h1>

    <section class="panel">
        <div class="panel-head">
            <h2>
                All Notifications
                @if ($unread)
                    <span class="badge green">{{ $unread }} new</span>
                @endif
            </h2>
            @if ($unread)
                <form method="POST" action="{{ route('staff.notifications.read') }}">
                    @csrf
                    <button class="btn">Mark all as read</button>
                </form>
            @endif
        </div>

        <div class="toolbar">
            @foreach ($filters as $option)
                <a href="{{ route('staff.notifications', ['filter' => $option]) }}" @class(['chip', 'active' => $filter === $option])>{{ $option }}</a>
            @endforeach
        </div>
    </section>

    @forelse ($notifications as $notification)
        <section @class(['panel', 'note', 'read' => $notification->is_read])>
            <span @class(['note-icon', $notification->colour])>
                <span class="material-symbols-outlined">{{ $notification->icon }}</span>
            </span>
            <div>
                <b>{{ $notification->title }}</b>
                <p>{{ $notification->body }}</p>
                <small>{{ $notification->created_at->diffForHumans() }}</small>
            </div>
            @unless ($notification->is_read)
                <i class="unread"></i>
            @endunless
        </section>
    @empty
        <section class="panel center grey">No notifications found.</section>
    @endforelse
@endsection
