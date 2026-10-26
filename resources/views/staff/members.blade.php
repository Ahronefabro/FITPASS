@extends('staff.layout')
@section('title', 'Members')

@section('content')
    <h1>Members</h1>
    @include('partials.cards')

    @if ($pending->isNotEmpty())
        <section class="panel alt">
            <h2>App Sign-ups Waiting for Payment</h2>
            <div class="table-wrap">
                <table>
                    <tr><th>Name</th><th>Type</th><th>Contact</th><th>Signed up</th><th>Amount</th><th class="right">Action</th></tr>
                    @foreach ($pending as $member)
                        <tr>
                            <td>
                                <span class="who"><span class="avatar">{{ $member->initials }}</span>{{ $member->name }}</span>
                                <small class="grey">{{ $member->email }}</small>
                            </td>
                            <td>{{ $member->plan }}</td>
                            <td>{{ $member->contact ?: '----' }}</td>
                            <td>{{ $member->created_at->format('M j, g:i A') }}</td>
                            <td>₱{{ number_format($prices[$member->plan]) }}</td>
                            <td class="right">
                                <form class="inline" method="POST" action="{{ route('staff.members.approve', $member->id) }}"
                                    data-confirm="Confirm ₱{{ number_format($prices[$member->plan]) }} payment from {{ $member->name }}?">
                                    @csrf
                                    <select name="method" aria-label="Payment method">
                                        @foreach ($methods as $method)
                                            <option value="{{ $method }}">{{ $method }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn primary">CONFIRM PAYMENT</button>
                                </form>
                                <form class="inline" method="POST" action="{{ route('staff.members.reject', $member->id) }}"
                                    data-confirm="Remove the sign-up from {{ $member->name }}?">
                                    @csrf @method('DELETE')
                                    <button class="icon-btn red" title="Reject"><span class="material-symbols-outlined">delete</span></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </section>
    @endif

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
                        <td>
                            <span class="who">
                                <span class="avatar">
                                    @if ($member->photo_url)
                                        <img src="{{ $member->photo_url }}" alt="" loading="lazy">
                                    @else
                                        {{ $member->initials }}
                                    @endif
                                </span>
                                {{ $member->name }}
                            </span>
                        </td>
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
                            <button class="icon-btn" title="Show QR code" data-open="member-qr"
                                data-name="{{ $member->name }}"
                                data-code="{{ $member->qr_code }}">
                                <span class="material-symbols-outlined">qr_code_2</span>
                            </button>
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

    <dialog id="member-qr" class="member-qr">
        <h2 id="qr-name">MEMBER QR CODE</h2>
        <div id="qr-image" class="qr-image"></div>
        <code id="qr-code"></code>
        <p class="grey">Scan this at the front desk to check in and out.</p>
        <div class="actions">
            <button type="button" class="btn" data-close>Close</button>
            <button type="button" class="btn primary" id="qr-print">PRINT</button>
        </div>
    </dialog>

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
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    // Draw the member's code as a QR image the scanner page can read.
    document.addEventListener('click', e => {
        const button = e.target.closest('[data-open="member-qr"]');
        if (!button) return;

        const image = document.getElementById('qr-image');
        image.innerHTML = '';
        document.getElementById('qr-name').textContent = button.dataset.name.toUpperCase();
        document.getElementById('qr-code').textContent = button.dataset.code;

        if (typeof QRCode === 'undefined') {
            image.innerHTML = '<p style="color: #000">QR library did not load. The code below can be typed in on the scanner.</p>';
            return;
        }

        new QRCode(image, {text: button.dataset.code, width: 220, height: 220, correctLevel: QRCode.CorrectLevel.M});
    });

    // Print just the card: the name, the QR image and the code.
    document.getElementById('qr-print').addEventListener('click', () => {
        const name = document.getElementById('qr-name').textContent;
        const code = document.getElementById('qr-code').textContent;
        const src = document.querySelector('#qr-image canvas')?.toDataURL() || document.querySelector('#qr-image img')?.src;
        if (!src) return;

        const win = window.open('', '_blank', 'width=400,height=500');
        if (!win) return;
        const page = win.document;
        page.title = code;
        page.body.style.cssText = 'font-family: sans-serif; text-align: center; padding: 24px';

        const heading = page.createElement('h2');
        heading.textContent = name;
        const img = page.createElement('img');
        img.width = img.height = 240;
        const label = page.createElement('p');
        label.style.cssText = 'font-size: 18px; letter-spacing: 2px';
        label.textContent = code;
        page.body.append(heading, img, label);

        img.onload = () => { win.print(); win.close(); };
        img.src = src;
    });

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
