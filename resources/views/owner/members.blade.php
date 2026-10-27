@extends('layouts.app')
@section('title', 'Members')

@section('content')
    @php $tones = ['Active' => 'green', 'Expiring soon' => 'orange', 'Expired' => 'red']; @endphp

    <h1>Members</h1>
    @include('partials.cards')

    <section class="panel alt">
        <h2>All Members</h2>
        <div class="toolbar">
            @foreach (['All', ...$plans, 'Expiring soon'] as $option)
                <a href="{{ route('members.index', ['filter' => $option, 'view' => $view]) }}" @class(['chip', 'active' => $filter === $option])>{{ $option }}</a>
            @endforeach
            <span class="spacer"></span>
            <span class="toggle">
                <a href="{{ route('members.index', ['filter' => $filter, 'view' => 'table']) }}" @class(['active' => $view === 'table'])>Table</a>
                <a href="{{ route('members.index', ['filter' => $filter, 'view' => 'grid']) }}" @class(['active' => $view === 'grid'])>Grid</a>
            </span>
            <button class="btn primary" data-open="add-member">+ Manual Entry</button>
        </div>

        @if ($members->isEmpty())
            <p class="grey">No members match this filter.</p>
        @elseif ($view === 'grid')
            <div class="tiles">
                @foreach ($members as $member)
                    <div class="tile">
                        <b>{{ $member->name }}</b>
                        <small>{{ $member->plan }} • {{ $member->membership_type }}</small>
                        <span @class(['badge', $tones[$member->status]])>{{ $member->status }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>Name</th><th>Plan</th><th>Type</th><th>Joined</th><th>Expires</th><th>Status</th></tr>
                    @foreach ($members as $member)
                        <tr>
                            <td><span class="who"><span class="avatar">{{ $member->initials }}</span>{{ $member->name }}</span></td>
                            <td>{{ $member->plan }}</td>
                            <td>{{ $member->membership_type }}</td>
                            <td>{{ $member->joined_at->format('M j, Y') }}</td>
                            <td>{{ $member->expires_at->format('M j, Y') }}</td>
                            <td><span @class(['badge', $tones[$member->status]])>{{ $member->status }}</span></td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif

        @if ($members->hasPages())
            <div class="toolbar">
                @if ($members->previousPageUrl()) <a class="chip" href="{{ $members->previousPageUrl() }}">← Previous</a> @endif
                <span class="spacer"></span>
                @if ($members->nextPageUrl()) <a class="chip" href="{{ $members->nextPageUrl() }}">Next →</a> @endif
            </div>
        @endif
    </section>

    {{-- What each plan costs. New sign-ups, at the desk and in the app, are charged these. --}}
    <section class="panel alt">
        <div class="panel-head">
            <h2>Plans &amp; Prices</h2>
            <button class="btn primary" data-open="add-plan">+ Add Plan</button>
        </div>
        <div class="table-wrap">
            <table>
                <tr><th>Plan</th><th>Price (₱)</th><th>Colour</th><th>Members</th><th></th></tr>
                @foreach ($planRows as $row)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td colspan="2">
                            <form method="POST" action="{{ route('plans.update', $row) }}" class="inline plan-form">
                                @csrf @method('PATCH')
                                <input type="number" name="price" min="0" step="0.01" value="{{ $row->price + 0 }}" required aria-label="{{ $row->name }} price">
                                <input type="color" name="colour" value="{{ $row->colour }}" aria-label="{{ $row->name }} colour">
                                <button class="btn small">Save</button>
                            </form>
                        </td>
                        <td>{{ $row->members_count }}</td>
                        <td class="right">
                            {{-- Members keep the plan's name, so a plan in use cannot go. --}}
                            @if ($row->members_count === 0)
                                <form method="POST" action="{{ route('plans.destroy', $row) }}" data-confirm="Delete the {{ $row->name }} plan?">
                                    @csrf @method('DELETE')
                                    <button class="btn small danger">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </section>

    <dialog id="add-member">
        <h2>ADD MEMBER</h2>
        <form method="POST" action="{{ route('members.store') }}">
            @csrf
            @if ($errors->any()) <p class="error">{{ $errors->first() }}</p> @endif

            <label>Full name <input name="name" value="{{ old('name') }}" required></label>
            <label>Plan
                <select name="plan">
                    @foreach ($plans as $plan)
                        <option @selected(old('plan') === $plan)>{{ $plan }}</option>
                    @endforeach
                </select>
            </label>
            <label>Membership type
                <select name="membership_type">
                    @foreach ($types as $type)
                        <option @selected(old('membership_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </label>
            <label>Amount paid (₱) <input type="number" name="amount" min="0" step="0.01" value="{{ old('amount', 0) }}" required></label>
            <label>Joined <input type="date" name="joined_at" value="{{ old('joined_at', today()->toDateString()) }}" required></label>
            <label>Expires <input type="date" name="expires_at" value="{{ old('expires_at', today()->addMonth()->toDateString()) }}" required></label>

            <div class="actions">
                <button type="button" class="btn" data-close>Cancel</button>
                <button class="btn primary">+ Add Member</button>
            </div>
        </form>
    </dialog>

    <dialog id="add-plan">
        <h2>ADD PLAN</h2>
        <form method="POST" action="{{ route('plans.store') }}">
            @csrf
            @if ($errors->plan->any()) <p class="error">{{ $errors->plan->first() }}</p> @endif
            <label>Name <input name="plan_name" value="{{ old('plan_name') }}" required></label>
            <label>Price (₱) <input type="number" name="price" min="0" step="0.01" value="{{ old('price') }}" required></label>
            <label>Chart colour <input type="color" name="colour" value="{{ old('colour', '#FFC107') }}"></label>

            <div class="actions">
                <button type="button" class="btn" data-close>Cancel</button>
                <button class="btn primary">+ Add Plan</button>
            </div>
        </form>
    </dialog>
    @if ($errors->plan->any())
        <script>document.getElementById('add-plan').showModal();</script>
    @endif
@endsection