@extends('staff.layout')
@section('title', 'Members')

@section('content')
    <h1>Members</h1>
    @include('partials.cards')

    <section class="panel alt">
        <h2>Member List</h2>
        <div class="toolbar">
            <span class="spacer"></span>
            <button class="btn primary" data-open="add-member">+ Add Member</button>
        </div>

        <div class="table-wrap">
            <table>
                <tr><th>Name</th><th>Type</th><th>Contact</th><th>Code</th><th>Status</th><th class="right">Action</th></tr>
                @forelse ($members as $member)
                    <tr>
                        <td><span class="who"><span class="avatar">{{ $member->initials }}</span>{{ $member->name }}</span></td>
                        <td>{{ $member->plan }}</td>
                        <td>{{ $member->contact ?: '----' }}</td>
                        <td><code>{{ $member->qr_code }}</code></td>
                        <td>
                            <span @class(['badge', match ($member->status) {
                                'Active' => 'green',
                                'Expiring soon' => 'orange',
                                default => 'red',
                            }])>{{ $member->status }}</span>
                        </td>
                        <td class="right">
                            <button class="icon-btn" title="Edit" data-open="edit-member"
                                data-id="{{ $member->id }}"
                                data-name="{{ $member->name }}"
                                data-plan="{{ $member->plan }}"
                                data-contact="{{ $member->contact }}">
                                <span class="material-symbols-outlined">edit</span>
                            </button>
                            <form class="inline" method="POST" action="{{ route('staff.members.destroy', $member) }}"
                                data-confirm="Delete {{ $member->name }}?">
                                @csrf @method('DELETE')
                                <button class="icon-btn red" title="Delete"><span class="material-symbols-outlined">delete</span></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No members found.</td></tr>
                @endforelse
            </table>
        </div>
    </section>

    <dialog id="add-member" @if (old('name') && ! old('editing')) data-reopen @endif>
        <h2>ADD MEMBER</h2>
        <form method="POST" action="{{ route('staff.members.store') }}">
            @csrf
            @if ($errors->any()) <p class="error">{{ $errors->first() }}</p> @endif

            <label>Member name
                <input name="name" value="{{ old('editing') ? '' : old('name') }}" required>
            </label>
            <label>Contact number
                <input name="contact" value="{{ old('editing') ? '' : old('contact') }}">
            </label>
            <label>Member type
                <select name="plan" required>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan }}" @selected(! old('editing') && old('plan') === $plan)>{{ $plan }}</option>
                    @endforeach
                </select>
            </label>

            <div class="actions">
                <button type="button" class="btn" data-close>Cancel</button>
                <button class="btn primary">ADD</button>
            </div>
        </form>
    </dialog>

    <dialog id="edit-member" @if (old('editing')) data-reopen @endif>
        <h2>EDIT MEMBER</h2>
        <form method="POST" action="{{ route('staff.members.update', old('editing') ?: 0) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="editing" value="{{ old('editing') }}">
            @if ($errors->any()) <p class="error">{{ $errors->first() }}</p> @endif

            <label>Member name
                <input name="name" value="{{ old('editing') ? old('name') : '' }}" required>
            </label>
            <label>Contact number
                <input name="contact" value="{{ old('editing') ? old('contact') : '' }}">
            </label>
            <label>Member type
                <select name="plan" required>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan }}" @selected(old('editing') && old('plan') === $plan)>{{ $plan }}</option>
                    @endforeach
                </select>
            </label>

            <div class="actions">
                <button type="button" class="btn" data-close>Cancel</button>
                <button class="btn primary">SAVE</button>
            </div>
        </form>
    </dialog>
@endsection

@push('scripts')
<script>
    // The edit dialog is filled from the row's button, so one dialog serves every row.
    const editAction = @json(route('staff.members.update', ':id'));

    document.addEventListener('click', e => {
        const button = e.target.closest('[data-open="edit-member"]');
        if (!button) return;

        const form = document.querySelector('#edit-member form');
        form.action = editAction.replace(':id', button.dataset.id);
        form.elements.editing.value = button.dataset.id;
        form.elements.name.value = button.dataset.name ?? '';
        form.elements.contact.value = button.dataset.contact ?? '';
        form.elements.plan.value = button.dataset.plan ?? '';
    });
</script>
@endpush
