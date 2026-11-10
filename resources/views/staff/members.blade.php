@extends('staff.layout')
@section('title', 'Members')

@section('content')
    @php
        $tones  = ['Active' => 'green', 'Expiring soon' => 'orange'];
        $filter = $filter ?? request('filter', 'All');
        $view   = $view ?? (request('view') === 'grid' ? 'grid' : 'table');
        $link   = fn (array $q) => request()->fullUrlWithQuery($q + ['page' => null]);
    @endphp

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

    <section class="panel alt member-list">
        <h2>Member List</h2>

        <div class="toolbar">
            @foreach (['All', ...$plans, 'Expiring soon'] as $option)
                <a href="{{ $link(['filter' => $option]) }}" @class(['chip', 'active' => $filter === $option])>{{ $option }}</a>
            @endforeach
            <span class="spacer"></span>
            <span class="toggle">
                <a href="{{ $link(['view' => 'table']) }}" @class(['active' => $view === 'table'])>Table</a>
                <a href="{{ $link(['view' => 'grid']) }}" @class(['active' => $view === 'grid'])>Grid</a>
            </span>
        </div>

        @if ($members->isEmpty())
            <p class="grey">No members match this filter.</p>

        @elseif ($view === 'grid')
            <div class="tiles">
                @foreach ($members as $member)
                    <article class="tile">
                        <div class="tile__top">
                            <span class="avatar">
                                @if ($member->photo_url)
                                    <img src="{{ $member->photo_url }}" alt="" loading="lazy">
                                @else
                                    {{ $member->initials }}
                                @endif
                            </span>
                            <div class="tile__id">
                                <b>{{ $member->name }}</b>
                                <small>{{ $member->plan }}</small>
                            </div>
                            <span @class(['badge', $tones[$member->status] ?? 'red'])>{{ $member->status }}</span>
                        </div>

                        <dl class="tile__info">
                            <div><dt>Contact</dt><dd>{{ $member->contact ?: '----' }}</dd></div>
                            <div><dt>Code</dt><dd><code>{{ $member->qr_code }}</code></dd></div>
                        </dl>

                        <div class="tile__actions">
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
                        </div>
                    </article>
                @endforeach
            </div>

        @else
            <div class="table-wrap">
                <table>
                    <tr><th>Name</th><th>Type</th><th>Contact</th><th>Code</th><th>Status</th><th class="right">Action</th></tr>
                    @foreach ($members as $member)
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
                            <td><span @class(['badge', $tones[$member->status] ?? 'red'])>{{ $member->status }}</span></td>
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
                    @endforeach
                </table>
            </div>
        @endif

        {{-- Numbered pagination: Showing 1–10 of 14   ‹ 1 2 3 … 8 › --}}
        @if (method_exists($members, 'hasPages') && $members->hasPages())
            @php
                $last    = $members->lastPage();
                $current = min($members->currentPage(), $last);
                // Always show first, last, and the pages around the current one
                $pages = collect([1, $last])
                    ->merge(range(max(1, $current - 1), min($last, $current + 1)))
                    ->unique()->sort()->values();
                $shown = 0;
            @endphp

            <div class="pager">
                <span class="pager__info">
                    Showing {{ $members->firstItem() }}–{{ $members->lastItem() }} of {{ $members->total() }}
                </span>

                <nav class="pager__nav" aria-label="Pagination">
                    @if ($members->onFirstPage())
                        <span class="pager__btn disabled" aria-disabled="true">‹</span>
                    @else
                        <a class="pager__btn" href="{{ $link(['page' => $current - 1]) }}" aria-label="Previous page">‹</a>
                    @endif

                    @foreach ($pages as $page)
                        {{-- A gap of exactly one page shows that page instead of a lone "…" --}}
                        @if ($page - $shown === 2)
                            <a class="pager__btn" href="{{ $link(['page' => $page - 1]) }}">{{ $page - 1 }}</a>
                        @elseif ($page - $shown > 2)
                            <span class="pager__gap">…</span>
                        @endif

                        @if ($page === $current)
                            <span class="pager__btn active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pager__btn" href="{{ $link(['page' => $page]) }}">{{ $page }}</a>
                        @endif
                        @php $shown = $page; @endphp
                    @endforeach

                    @if ($members->hasMorePages())
                        <a class="pager__btn" href="{{ $link(['page' => $current + 1]) }}" aria-label="Next page">›</a>
                    @else
                        <span class="pager__btn disabled" aria-disabled="true">›</span>
                    @endif
                </nav>
            </div>
        @endif
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

    <style>
        /* Scoped to this page, so it will not clash with the layout's own chip/toggle/tile styles */
        .member-list .toolbar { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin: 14px 0 18px; }
        .member-list .spacer { flex: 1; }

        .member-list .chip {
            padding: 7px 16px; border-radius: 999px; font-size: 13px; font-weight: 600;
            color: #e5e7eb; text-decoration: none; border: 1px solid #343944;
            transition: border-color .15s, color .15s, background .15s;
        }
        .member-list .chip:hover { border-color: #4b5160; }
        .member-list .chip.active { color: #22c55e; border-color: #22c55e; background: rgba(34,197,94,.12); }

        .member-list .toggle { display: inline-flex; padding: 4px; gap: 4px; border-radius: 12px; background: #12141a; border: 1px solid #2f343d; }
        .member-list .toggle a {
            padding: 6px 16px; border-radius: 9px; font-size: 13px; font-weight: 700;
            color: #9ca3af; text-decoration: none; transition: background .15s, color .15s;
        }
        .member-list .toggle a:hover { color: #fff; }
        .member-list .toggle a.active { background: #22c55e; color: #05260f; }

        .member-list .tiles { display: grid; gap: 14px; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); }
        .member-list .tile {
            display: flex; flex-direction: column; gap: 14px; padding: 18px;
            border-radius: 14px; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.09);
            transition: border-color .15s, background .15s, transform .15s;
        }
        .member-list .tile:hover { border-color: rgba(34,197,94,.35); background: rgba(255,255,255,.055); transform: translateY(-2px); }
        .tile__top { display: flex; align-items: center; gap: 12px; }
        .tile .avatar { flex: none; width: 46px; height: 46px; font-size: 15px; overflow: hidden; }
        .tile .avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .tile__id { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
        .tile__id b { color: #fff; font-size: 15px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .tile__id small { color: #8b93a1; font-size: 13px; }
        .tile__info {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 0; padding: 12px 0;
            border-top: 1px solid rgba(255,255,255,.08); border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .tile__info dt { margin: 0 0 4px; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #8b93a1; }
        .tile__info dd { margin: 0; font-size: 14px; color: #e5e7eb; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .tile__actions { display: flex; align-items: center; justify-content: flex-end; gap: 6px; }
        .tile__actions form { margin: 0; }

        /* Pagination */
        .member-list .pager { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 18px; }
        .member-list .pager__info { font-size: 14px; color: #8b93a1; }
        .member-list .pager__nav { display: flex; align-items: center; gap: 8px; }
        .member-list .pager__btn {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 42px; height: 42px; padding: 0 12px; border-radius: 10px;
            background: #1a1d24; border: 1px solid #2f343d;
            color: #fff; font-size: 15px; font-weight: 700; text-decoration: none;
            transition: background .15s, border-color .15s, color .15s;
        }
        .member-list a.pager__btn:hover { border-color: #22c55e; color: #22c55e; }
        .member-list .pager__btn.active { background: #22c55e; border-color: #22c55e; color: #05260f; }
        .member-list .pager__btn.disabled { opacity: .35; cursor: not-allowed; }
        .member-list .pager__gap { color: #8b93a1; padding: 0 2px; }

        @media (max-width: 600px) {
            .member-list .tiles { grid-template-columns: 1fr; }
            .member-list .pager { justify-content: center; }
        }
    </style>
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