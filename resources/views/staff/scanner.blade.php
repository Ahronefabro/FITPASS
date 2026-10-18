@extends('staff.layout')
@section('title', 'Scanner')

@section('content')
    <h1>QR Check-in Scanner</h1>

    <div class="grid g-2">
        <section class="panel">
            <div class="panel-head">
                <h2>Camera</h2>
                <span id="scan-status" class="badge green">Ready to scan</span>
            </div>

            <div class="scanner">
                <div id="reader"></div>
                <div id="frame" class="frame">
                    <b>SCAN MEMBER QR CODE</b>
                    <small>Place the QR code inside the frame</small>
                </div>
                <div id="scanned" class="scanned" hidden>
                    <span class="material-symbols-outlined">check_circle</span>
                    <b>QR CODE SCANNED</b>
                    <small id="scanned-code"></small>
                </div>
            </div>

            <form id="code-form" class="toolbar" style="margin: 16px 0 0">
                <input id="code" class="code-input" placeholder="...or type the member code" autocomplete="off">
                <button class="btn primary">Look up</button>
            </form>
        </section>

        <section class="panel">
            <h2>Member Details</h2>

            <div class="who member-head">
                <span id="member-avatar" class="avatar"><span class="material-symbols-outlined">person</span></span>
                <div>
                    <b id="member-name">&mdash;</b>
                    <small id="member-meta">Scan a member QR code to see their details.</small>
                </div>
            </div>

            <div class="info-grid">
                <div><small>TIME</small><b id="member-time">&mdash;</b></div>
                <div><small>METHOD</small><b id="member-method" class="green">QR CODE</b></div>
                <div><small>MEMBERSHIP</small><b id="member-membership" class="green">&mdash;</b></div>
                <div><small>VISITS THIS MONTH</small><b id="member-visits">&mdash;</b></div>
            </div>

            <div id="member-actions" class="actions" hidden>
                <button id="scan-again" class="btn">SCAN AGAIN</button>
                <button id="confirm" class="btn primary">CHECK IN</button>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    const routes = {
        lookup: @json(route('staff.scanner.lookup')),
        record: @json(route('staff.scanner.record')),
    };
    const token = @json(csrf_token());

    const el = id => document.getElementById(id);
    const status = el('scan-status');
    const actions = el('member-actions');
    const confirmBtn = el('confirm');

    let code = null;
    let scanner = null;

    const say = (text, ok = true) => {
        status.textContent = text;
        status.className = 'badge ' + (ok ? 'green' : 'red');
    };

    const post = (url, body) => fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token},
        body: JSON.stringify(body),
    }).then(async response => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'Could not reach the server.');
        return data;
    });

    // Blank member card, camera live again, ready for the next person.
    function reset() {
        code = null;
        actions.hidden = true;
        el('frame').hidden = false;
        el('scanned').hidden = true;
        ['name', 'time', 'membership', 'visits'].forEach(f => el('member-' + f).innerHTML = '&mdash;');
        el('member-meta').textContent = 'Scan a member QR code to see their details.';
        el('member-avatar').classList.remove('found');

        try {
            scanner?.resume();
        } catch (error) {
            // Already live, which is exactly where we want it.
        }
    }

    function fill(member) {
        el('member-name').textContent = member.name;
        el('member-meta').textContent = member.meta;
        el('member-time').textContent = member.time;
        el('member-method').textContent = member.method;
        el('member-membership').textContent = member.membership;
        el('member-visits').textContent = member.visits;
        el('member-avatar').classList.add('found');
        confirmBtn.textContent = member.action.toUpperCase();
        confirmBtn.classList.toggle('warn', member.action === 'Check Out');
        actions.hidden = false;
    }

    function lookup(value) {
        code = value;
        say('Looking up...');
        el('frame').hidden = true;
        el('scanned').hidden = false;
        el('scanned-code').textContent = value;

        post(routes.lookup, {code: value})
            .then(member => { fill(member); say('Member found'); })
            .catch(error => { say(error.message, false); reset(); });
    }

    confirmBtn.addEventListener('click', () => {
        if (!code) return;
        confirmBtn.disabled = true;

        post(routes.record, {code})
            .then(result => { say(result.message); reset(); })
            .catch(error => say(error.message, false))
            .finally(() => confirmBtn.disabled = false);
    });

    el('scan-again').addEventListener('click', reset);

    el('code-form').addEventListener('submit', event => {
        event.preventDefault();
        const value = el('code').value.trim();
        if (!value) return;
        el('code').value = '';
        lookup(value);
    });

    // The camera is a convenience: if it is missing or blocked, the code field still works.
    if (typeof Html5Qrcode !== 'undefined') {
        scanner = new Html5Qrcode('reader');
        scanner.start({facingMode: 'environment'}, {fps: 10, qrbox: 220}, text => {
            scanner.pause(true);
            lookup(text);
        }).catch(() => {
            scanner = null;
            el('reader').innerHTML = '<p class="center grey">Camera unavailable. Type the member code below instead.</p>';
        });
    } else {
        el('reader').innerHTML = '<p class="center grey">Scanner library did not load. Type the member code below instead.</p>';
    }
</script>
@endpush
