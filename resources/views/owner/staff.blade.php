@extends('layouts.app')
@section('title', 'Staff Management')

@section('content')
    @php $tones = ['Active' => 'green', 'Inactive' => 'red', 'Off Duty' => 'grey']; @endphp

    <h1>Staff Management</h1>
    @include('partials.cards')

    <div class="toolbar">
        @foreach (['All', 'Front Desk', 'Trainers', 'Admins'] as $option)
            <a href="{{ route('staff.index', ['filter' => $option]) }}" @class(['chip', 'active' => $filter === $option])>{{ $option }}</a>
        @endforeach
        <span class="spacer"></span>
        <button class="btn primary" data-open="add-staff">+ Add Staff</button>
    </div>

    <section class="panel alt">
        <div class="table-wrap">
            <table>
                <tr><th>Staff</th><th>Role</th><th>Shift</th><th>Status</th><th>Last login</th><th></th></tr>
                @forelse ($staff as $person)
                    <tr>
                        <td><span class="who"><span class="avatar">{{ $person->initials }}</span>{{ $person->name }}</span></td>
                        <td>{{ $person->role }}</td>
                        <td>{{ $person->shift_label }}</td>
                        <td><span @class(['badge', $tones[$person->status] ?? 'grey'])>{{ strtoupper($person->status) }}</span></td>
                        <td>{{ $person->last_login_at?->format('M j, g:i A') ?? 'Never' }}</td>
                        <td class="right">
                            {{-- Pencil menu: status and delete --}}
                            <details class="menu">
                                <summary title="Edit status"><span class="material-symbols-outlined">edit</span></summary>
                                <div class="menu-list">
                                    <form method="POST" action="{{ route('staff.update', $person) }}">
                                        @csrf @method('PATCH')
                                        @foreach ($statuses as $status)
                                            <button name="status" value="{{ $status }}" @class(['current' => $person->status === $status])>
                                                <i @class(['dot', $tones[$status]])></i>{{ $status }}
                                            </button>
                                        @endforeach
                                    </form>
                                    <hr>
                                    <form method="POST" action="{{ route('staff.destroy', $person) }}" data-confirm="Remove {{ $person->name }}? This cannot be undone.">
                                        @csrf @method('DELETE')
                                        <button class="red"><span class="material-symbols-outlined">delete</span>Delete</button>
                                    </form>
                                </div>
                            </details>

                            {{-- Menu button: role --}}
                            <details class="menu">
                                <summary title="Change role"><span class="material-symbols-outlined">menu</span></summary>
                                <div class="menu-list">
                                    <form method="POST" action="{{ route('staff.update', $person) }}">
                                        @csrf @method('PATCH')
                                        @foreach ($roles as $role)
                                            <button name="role" value="{{ $role }}" @class(['current' => $person->role === $role])>{{ $role }}</button>
                                        @endforeach
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No staff in this group.</td></tr>
                @endforelse
            </table>
        </div>
    </section>

    <dialog id="add-staff">
        <h2>ADD STAFF</h2>
        <form method="POST" action="{{ route('staff.store') }}">
            @csrf
            @if ($errors->any()) <p class="error">{{ $errors->first() }}</p> @endif

            <label>Full name <input name="name" value="{{ old('name') }}" required></label>
            <label>Role
                <select name="role">
                    @foreach ($roles as $role)
                        <option @selected(old('role') === $role)>{{ $role }}</option>
                    @endforeach
                </select>
            </label>
            <label>Shift start <input type="time" name="shift_start" value="{{ old('shift_start', '06:00') }}" required></label>
            <label>Status
                <select name="status">
                    @foreach ($statuses as $status)
                        <option @selected(old('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </label>

            <div class="actions">
                <button type="button" class="btn" data-close>Cancel</button>
                <button class="btn primary">+ Add Staff</button>
            </div>
        </form>
    </dialog>
@endsection