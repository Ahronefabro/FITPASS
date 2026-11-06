@extends('staff.layout')
@section('title', 'Manual Time in/out')

@section('content')
    <h1>Manual Time in / out</h1>

    <section class="panel">
        <h2>Manual Time Entry</h2>
        <form method="POST" action="{{ route('staff.manual-time.store') }}" id="manualTimeForm">
            @csrf
            <input type="hidden" name="payment_confirmed" id="paymentConfirmed" value="">
            <input type="hidden" name="walk_in_type" id="walkInType" value="">
            <input type="hidden" name="payment_method" id="paymentMethod" value="">
            @if ($errors->any()) <p class="error">{{ $errors->first() }}</p> @endif

            <div class="name-search">
                <label>Name <small>walk-in or first-timer</small>
                    <div class="name-search__box">
                        <input id="nameSearch" name="guest_name" value="{{ old('guest_name') }}"
                               placeholder="Type the name" autocomplete="off" required>
                        <button type="button" id="nameSearchBtn" class="name-search__btn"
                                title="Search past walk-ins" aria-label="Search past walk-ins"
                                aria-expanded="false" aria-controls="namePanel">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </button>
                    </div>
                </label>

                <div id="namePanel" class="name-panel" hidden>
                    <div class="name-panel__head">
                        <span>Past walk-ins</span>
                        <span id="namePanelCount"></span>
                    </div>

                    <div class="name-panel__search">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" id="namePanelSearch" placeholder="Search by name…" autocomplete="off">
                    </div>

                    <ul id="namePanelList" role="listbox"></ul>

                    <div class="name-panel__foot">
                        <span><kbd>↑</kbd> <kbd>↓</kbd> to move</span>
                        <span><kbd>Enter</kbd> to select</span>
                        <span><kbd>Esc</kbd> to close</span>
                    </div>
                </div>
            </div>

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

        <dialog id="payModal" class="pay-modal" aria-labelledby="payModalTitle">
            <div class="pay-modal__body">
                <div class="pay-modal__icon" aria-hidden="true">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                </div>
                <h3 id="payModalTitle">Confirm payment action</h3>
                <p class="pay-modal__who">Walk-in / first-timer: <strong id="payModalName"></strong></p>
                <div class="pay-modal__options" role="radiogroup" aria-label="Walk-in type">
                    <label class="pay-opt">
                        <input type="radio" name="pay_opt" value="regular" checked>
                        <span class="pay-opt__card">
                            <span class="pay-opt__label">Regular</span>
                            <span class="pay-opt__price">₱100</span>
                        </span>
                    </label>
                    <label class="pay-opt">
                        <input type="radio" name="pay_opt" value="student">
                        <span class="pay-opt__card">
                            <span class="pay-opt__label">Student</span>
                            <span class="pay-opt__price">₱80</span>
                        </span>
                    </label>
                </div>
                <div class="pay-modal__methods" role="radiogroup" aria-label="Payment method">
                    <span class="pay-modal__methods-label">Paid via</span>
                    <label class="pay-method">
                        <input type="radio" name="pay_method" value="Cash" checked>
                        <span>Cash</span>
                    </label>
                    <label class="pay-method">
                        <input type="radio" name="pay_method" value="Gcash">
                        <span>GCash</span>
                    </label>
                </div>
                <p class="pay-modal__text">Collect the walk-in fee, then confirm to record this time in.</p>
                <div class="pay-modal__actions">
                    <button type="button" id="payModalCancel" class="pay-modal__btn pay-modal__btn--ghost">Cancel</button>
                    <button type="button" id="payModalConfirm" class="pay-modal__btn pay-modal__btn--primary" autofocus>Confirm ₱100 payment</button>
                </div>
            </div>
        </dialog>

        @if (session('payment_confirmed'))
            @php($paid = session('payment_confirmed'))
            <dialog id="paidModal" class="pay-modal paid-modal" aria-labelledby="paidTitle">
                <div class="pay-modal__body">
                    <div class="paid-check" aria-hidden="true">
                        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 id="paidTitle">Payment Confirmed</h3>
                    <p class="pay-modal__who"><strong>{{ $paid['name'] }}</strong></p>
                    <p class="pay-modal__text">{{ ucfirst($paid['type']) }} walk-in · {{ $paid['method'] }}</p>
                    <div class="paid-amount">₱{{ number_format($paid['amount']) }}</div>
                    <button type="button" id="paidDone" class="pay-modal__btn pay-modal__btn--primary" autofocus>Done</button>
                </div>
                <div class="paid-bar"></div>
            </dialog>
        @endif
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

    <style>
        .name-search { position: relative; }
        .name-search__box { position: relative; }
        .name-search__box input { width: 100%; box-sizing: border-box; padding-right: 52px; }
        .name-search__btn {
            position: absolute; top: 50%; right: 8px; transform: translateY(-50%);
            width: 36px; height: 36px; display: grid; place-items: center;
            background: transparent; border: 0; border-radius: 8px;
            color: #22c55e; cursor: pointer; transition: background .15s;
        }
        .name-search__btn:hover,
        .name-search__btn[aria-expanded="true"] { background: rgba(34,197,94,.15); }

        /* Panel: compact card anchored under the search icon */
        .name-search .name-panel {
            position: absolute; top: calc(100% + 8px); right: 0; z-index: 50;
            width: min(440px, 100%); padding: 14px;
            background: #1a1d24; border: 1px solid #2f343d; border-radius: 14px;
            box-shadow: 0 18px 44px rgba(0,0,0,.55);
            animation: namePanelIn .14s ease-out;
        }
        @keyframes namePanelIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: reduce) { .name-search .name-panel { animation: none; } }

        .name-panel__head {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 10px; font-size: 11px; letter-spacing: .08em;
            text-transform: uppercase; color: #8b93a1; font-weight: 600;
        }
        .name-panel__head #namePanelCount { text-transform: none; letter-spacing: 0; font-weight: 500; }

        .name-panel__search { position: relative; margin-bottom: 10px; }
        .name-panel__search svg {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            color: #8b93a1; pointer-events: none;
        }
        .name-search .name-panel .name-panel__search input {
            width: 100%; box-sizing: border-box; margin: 0; padding: 11px 12px 11px 38px;
            background: #12141a; border: 1px solid #2f343d; border-radius: 10px;
            color: #fff; outline: none; box-shadow: none;
        }
        .name-search .name-panel .name-panel__search input:focus {
            border-color: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,.15);
        }

        .name-panel ul { list-style: none; margin: 0; padding: 0; max-height: 264px; overflow-y: auto; }
        .name-panel li.item {
            display: flex; align-items: center; gap: 12px;
            padding: 9px 10px; border-radius: 10px; cursor: pointer; color: #fff;
        }
        .name-panel li.item.active { background: rgba(34,197,94,.14); }
        .name-panel__avatar {
            flex: none; width: 34px; height: 34px; border-radius: 50%;
            display: grid; place-items: center; font-size: 12px; font-weight: 700;
            background: #2a2e37; color: #e5e7eb; transition: background .15s, color .15s;
        }
        .name-panel li.item.active .name-panel__avatar { background: #22c55e; color: #05260f; }
        .name-panel__name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .name-panel__name mark { background: none; color: #22c55e; font-weight: 700; }
        .name-panel__when { flex: none; font-size: 12px; color: #8b93a1; }
        .name-panel li.empty { padding: 22px 10px; text-align: center; color: #8b93a1; cursor: default; }

        .name-panel__foot {
            display: flex; gap: 14px; flex-wrap: wrap; margin-top: 10px; padding-top: 10px;
            border-top: 1px solid #2a2e37; font-size: 11px; color: #6b7280;
        }
        .name-panel__foot kbd {
            padding: 1px 5px; border: 1px solid #343944; border-radius: 4px;
            background: #12141a; font: inherit; color: #9ca3af;
        }
        @media (max-width: 600px) { .name-panel__foot { display: none; } }

        /* Confirm payment modal */
        .pay-modal {
            width: min(420px, calc(100% - 32px)); padding: 0; color: #fff;
            background: #1a1d24; border: 1px solid #2f343d; border-radius: 16px;
            box-shadow: 0 24px 60px rgba(0,0,0,.6);
        }
        .pay-modal[open] { animation: payModalIn .16s ease-out; }
        .pay-modal::backdrop { background: rgba(0,0,0,.65); backdrop-filter: blur(2px); }
        @keyframes payModalIn {
            from { opacity: 0; transform: translateY(8px) scale(.98); }
            to   { opacity: 1; transform: none; }
        }
        @media (prefers-reduced-motion: reduce) { .pay-modal[open] { animation: none; } }

        .pay-modal__body { padding: 28px 24px 22px; text-align: center; }
        .pay-modal__icon {
            width: 56px; height: 56px; margin: 0 auto 14px; display: grid; place-items: center;
            border-radius: 50%; background: rgba(34,197,94,.15); color: #22c55e;
        }
        .pay-modal h3 { margin: 0 0 10px; font-size: 20px; }
        .pay-modal__who { margin: 0 0 6px; color: #d1d5db; }
        .pay-modal__who strong { color: #22c55e; }
        .pay-modal__text { margin: 0 0 22px; font-size: 14px; color: #8b93a1; line-height: 1.5; }
        .pay-modal__actions { display: flex; gap: 10px; }
        .pay-modal .pay-modal__btn {
            flex: 1; padding: 12px 16px; border-radius: 10px; font-weight: 700;
            font-size: 14px; cursor: pointer; transition: filter .15s, background .15s;
        }
        .pay-modal .pay-modal__btn--ghost { background: transparent; color: #e5e7eb; border: 1px solid #343944; }
        .pay-modal .pay-modal__btn--ghost:hover { background: #242830; }
        .pay-modal .pay-modal__btn--primary { background: #22c55e; color: #05260f; border: 1px solid #22c55e; }
        .pay-modal .pay-modal__btn--primary:hover { filter: brightness(1.08); }
        .pay-modal .pay-modal__btn:disabled { opacity: .7; cursor: default; }

        .pay-modal__options { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 16px 0 14px; }
        .pay-modal .pay-opt { position: relative; display: block; cursor: pointer; margin: 0; }
        .pay-modal .pay-opt input[type="radio"] {
            position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none;
        }
        .pay-opt__card {
            display: flex; flex-direction: column; align-items: center; gap: 4px;
            padding: 14px 10px; border: 1px solid #343944; border-radius: 12px;
            background: #12141a; transition: border-color .15s, background .15s;
        }
        .pay-opt__label { font-size: 13px; color: #9ca3af; }
        .pay-opt__price { font-size: 22px; font-weight: 800; color: #fff; }
        .pay-opt:hover .pay-opt__card { border-color: #4b5160; }
        .pay-modal .pay-opt input[type="radio"]:checked + .pay-opt__card {
            border-color: #22c55e; background: rgba(34,197,94,.12);
        }
        .pay-modal .pay-opt input[type="radio"]:checked + .pay-opt__card .pay-opt__price { color: #22c55e; }
        .pay-modal .pay-opt input[type="radio"]:focus-visible + .pay-opt__card {
            box-shadow: 0 0 0 3px rgba(34,197,94,.25);
        }

        .pay-modal__methods { display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 14px; }
        .pay-modal__methods-label { margin-right: 4px; font-size: 13px; color: #8b93a1; }
        .pay-modal .pay-method { position: relative; display: block; margin: 0; cursor: pointer; }
        .pay-modal .pay-method input[type="radio"] {
            position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none;
        }
        .pay-method span {
            display: block; padding: 7px 16px; border: 1px solid #343944; border-radius: 999px;
            background: #12141a; color: #9ca3af; font-size: 13px; font-weight: 600;
            transition: border-color .15s, color .15s, background .15s;
        }
        .pay-modal .pay-method input[type="radio"]:checked + span {
            border-color: #22c55e; color: #22c55e; background: rgba(34,197,94,.12);
        }
        .pay-modal .pay-method input[type="radio"]:focus-visible + span { box-shadow: 0 0 0 3px rgba(34,197,94,.25); }

        /* Payment confirmed popup */
        .paid-modal { overflow: hidden; width: min(360px, calc(100% - 32px)); }
        .paid-check {
            width: 76px; height: 76px; margin: 0 auto 14px; display: grid; place-items: center;
            border-radius: 50%; background: rgba(34,197,94,.15); color: #22c55e;
            animation: paidPop .35s cubic-bezier(.2,1.4,.4,1);
        }
        .paid-check path { stroke-dasharray: 24; stroke-dashoffset: 24; animation: paidDraw .35s .15s ease-out forwards; }
        @keyframes paidPop { from { transform: scale(.5); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @keyframes paidDraw { to { stroke-dashoffset: 0; } }
        .paid-amount { margin: 4px 0 20px; font-size: 34px; font-weight: 800; color: #22c55e; }
        .paid-modal .pay-modal__btn { width: 100%; flex: none; }
        .paid-bar { height: 3px; background: #22c55e; transform-origin: left; animation: paidBar 3.5s linear forwards; }
        @keyframes paidBar { from { transform: scaleX(1); } to { transform: scaleX(0); } }
        @media (prefers-reduced-motion: reduce) {
            .paid-check, .paid-check path { animation: none; stroke-dashoffset: 0; }
        }
    </style>

    <script>
    (() => {
        const input = document.getElementById('nameSearch');
        const btn = document.getElementById('nameSearchBtn');
        const panel = document.getElementById('namePanel');
        const search = document.getElementById('namePanelSearch');
        const list = document.getElementById('namePanelList');
        const count = document.getElementById('namePanelCount');
        const wrapper = input.closest('.name-search');
        const url = @json(route('staff.manual-time.search'));
        let timer, activeIndex = -1, requestId = 0;

        const items = () => [...list.querySelectorAll('li.item')];

        function initials(name) {
            return name.trim().split(/\s+/).slice(0, 2).map(w => w[0] || '').join('').toUpperCase();
        }

        // "Today", "Yesterday" or "Nov 3" for the last time this person walked in.
        function whenLabel(ts) {
            if (!ts) return '';
            const d = new Date(String(ts).replace(' ', 'T'));
            if (isNaN(d)) return '';
            const startOf = x => new Date(x.getFullYear(), x.getMonth(), x.getDate());
            const days = Math.round((startOf(new Date()) - startOf(d)) / 86400000);
            if (days === 0) return 'Today';
            if (days === 1) return 'Yesterday';
            return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        }

        // Bold + green the part of the name that matches what was typed (no innerHTML, so it's XSS-safe).
        function appendHighlighted(el, text, query) {
            const q = query.trim();
            const i = q ? text.toLowerCase().indexOf(q.toLowerCase()) : -1;
            if (i < 0) { el.textContent = text; return; }
            el.append(text.slice(0, i));
            const mark = document.createElement('mark');
            mark.textContent = text.slice(i, i + q.length);
            el.append(mark, text.slice(i + q.length));
        }

        function setMessage(text) {
            list.innerHTML = '';
            const li = document.createElement('li');
            li.className = 'empty';
            li.textContent = text;
            list.appendChild(li);
            count.textContent = '';
        }

        function setActive(i) {
            const all = items();
            if (!all.length) return;
            activeIndex = (i + all.length) % all.length;
            all.forEach((li, idx) => li.classList.toggle('active', idx === activeIndex));
            all[activeIndex].scrollIntoView({ block: 'nearest' });
        }

        function renderList(names, query) {
            list.innerHTML = '';
            activeIndex = -1;
            if (!names.length) {
                setMessage(query.trim() ? `No past walk-ins match “${query.trim()}”.` : 'No past walk-ins yet.');
                return;
            }
            names.forEach((n, idx) => {
                const li = document.createElement('li');
                li.className = 'item';
                li.setAttribute('role', 'option');
                li.dataset.name = n.name;

                const av = document.createElement('span');
                av.className = 'name-panel__avatar';
                av.textContent = initials(n.name);

                const nm = document.createElement('span');
                nm.className = 'name-panel__name';
                appendHighlighted(nm, n.name, query);

                const wh = document.createElement('span');
                wh.className = 'name-panel__when';
                wh.textContent = whenLabel(n.last_visit);

                li.append(av, nm, wh);
                li.addEventListener('click', () => pick(n.name));
                li.addEventListener('mousemove', () => { if (activeIndex !== idx) setActive(idx); });
                list.appendChild(li);
            });
            count.textContent = names.length + (names.length === 1 ? ' name' : ' names');
        }

        async function load(query) {
            const mine = ++requestId; // ignore out-of-order responses
            try {
                const res = await fetch(`${url}?q=${encodeURIComponent(query.trim())}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const names = await res.json();
                if (mine === requestId) renderList(names, query);
            } catch (e) {
                if (mine === requestId) setMessage('Could not load names.');
            }
        }

        function pick(name) {
            input.value = name;
            closePanel();
            input.focus();
        }

        function openPanel() {
            panel.hidden = false;
            btn.setAttribute('aria-expanded', 'true');
            search.value = '';
            setMessage('Loading…');
            load('');
            search.focus();
        }

        function closePanel() {
            panel.hidden = true;
            btn.setAttribute('aria-expanded', 'false');
        }

        btn.addEventListener('click', () => (panel.hidden ? openPanel() : closePanel()));

        search.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => load(search.value), 200);
        });

        search.addEventListener('keydown', e => {
            if (e.key === 'ArrowDown') { e.preventDefault(); setActive(activeIndex + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(activeIndex - 1); }
            else if (e.key === 'Enter') {
                e.preventDefault(); // don't submit the form
                const all = items();
                const target = all[activeIndex] || all[0];
                if (target) pick(target.dataset.name);
            } else if (e.key === 'Escape') {
                closePanel();
                input.focus();
            }
        });

        document.addEventListener('click', e => {
            if (!panel.hidden && !wrapper.contains(e.target)) closePanel();
        });
    })();
    </script>

    <script>
    // Time In for a walk-in / first-timer asks to confirm payment first.
    (() => {
        const form = document.getElementById('manualTimeForm');
        const dialog = document.getElementById('payModal');
        const nameEl = document.getElementById('payModalName');
        const flag = document.getElementById('paymentConfirmed');
        const confirmBtn = document.getElementById('payModalConfirm');
        const cancelBtn = document.getElementById('payModalCancel');
        const nameInput = document.getElementById('nameSearch');
        let confirmed = false;
        const fees = { regular: 100, student: 80 };
        const typeFlag = document.getElementById('walkInType');
        const methodFlag = document.getElementById('paymentMethod');
        const selectedMethod = () => dialog.querySelector('input[name="pay_method"]:checked').value;
        const selectedType = () => dialog.querySelector('input[name="pay_opt"]:checked').value;
        const updateLabel = () => { confirmBtn.textContent = `Confirm ₱${fees[selectedType()]} payment`; };
        dialog.querySelectorAll('input[name="pay_opt"]').forEach(r => r.addEventListener('change', updateLabel));

        form.addEventListener('submit', e => {
            const action = form.querySelector('input[name="action"]:checked')?.value;
            if (action !== 'Check In' || confirmed) return; // Time Out goes straight through
            e.preventDefault();
            nameEl.textContent = nameInput.value.trim();
            dialog.querySelector('input[name="pay_opt"][value="regular"]').checked = true;
            dialog.querySelector('input[name="pay_method"][value="Cash"]').checked = true;
            updateLabel();
            dialog.showModal();
        });

        confirmBtn.addEventListener('click', () => {
            confirmed = true;
            typeFlag.value = selectedType();
            methodFlag.value = selectedMethod();
            flag.value = '1';
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Recording…';
            form.submit();
        });

        cancelBtn.addEventListener('click', () => dialog.close());

        // Clicking the dark backdrop closes it (Esc already works on <dialog>).
        dialog.addEventListener('click', e => { if (e.target === dialog) dialog.close(); });
    })();
    </script>

    @if (session('payment_confirmed'))
    <script>
    // Show the "Payment Confirmed" popup after a successful Time In, then close it by itself.
    (() => {
        const dlg = document.getElementById('paidModal');
        if (!dlg) return;
        const close = () => { if (dlg.open) dlg.close(); };
        dlg.showModal();
        document.getElementById('paidDone').addEventListener('click', close);
        dlg.addEventListener('click', e => { if (e.target === dlg) close(); });
        setTimeout(close, 3500);
    })();
    </script>
    @endif
@endsection