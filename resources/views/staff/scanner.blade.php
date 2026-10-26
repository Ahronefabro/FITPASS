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
                    <span id="scanned-icon" class="material-symbols-outlined">qr_code_scanner</span>
                    <b id="scanned-title">QR CODE SCANNED</b>
                    <small id="scanned-code"></small>
                </div>
            </div>

            <form id="code-form" class="toolbar" style="margin: 16px 0 0">
                <input id="code" class="code-input" placeholder="...or type the member code" autocomplete="off">
                <button class="btn primary">Record</button>
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

            <div id="member-result" class="scan-result" hidden></div>
            <p class="grey" style="margin: 12px 0 0; font-size: 12px">First scan checks a member in, the next scan checks them out.</p>
        </section>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    const record = @json(route('staff.scanner.record'));
    const token = @json(csrf_token());

    const el = id => document.getElementById(id);
    const status = el('scan-status');
    const result = el('member-result');

    // How long the result stays up before the camera takes the next person,
    // and how long the same code is ignored after it was recorded. Without the
    // second, a member still holding their code to the camera would be checked
    // out the moment the camera came back.
    const SHOW_FOR = 3000;
    const IGNORE_SAME_CODE_FOR = 10000;

    let busy = false;
    let scanner = null;
    let last = {code: null, at: 0};
    let timer = null;

    // html5-qrcode throws if it is asked to pause or resume when it is not in
    // that state (still starting, already paused, or stopped), so these are safe to call any time.
    const pause = () => { try { scanner?.pause(true); } catch (error) {} };
    const resume = () => { try { scanner?.resume(); } catch (error) {} };

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

    // Camera frame back and live, ready for the next person. The member card
    // keeps showing who was scanned last until someone new is.
    function ready() {
        busy = false;
        el('frame').hidden = false;
        el('scanned').hidden = true;
        say('Ready to scan');
        resume();
    }

    function fill(member) {
        const out = member.action === 'Check Out';

        el('member-name').textContent = member.name;
        el('member-meta').textContent = member.meta;
        el('member-time').textContent = member.time;
        el('member-method').textContent = member.method;
        el('member-membership').textContent = member.membership;
        el('member-visits').textContent = member.visits;
        el('member-avatar').classList.add('found');

        result.textContent = out ? 'CHECKED OUT' : 'CHECKED IN';
        result.className = 'scan-result ' + (out ? 'out' : 'in');
        result.hidden = false;

        el('scanned-icon').textContent = out ? 'logout' : 'login';
        el('scanned-title').textContent = out ? 'CHECKED OUT' : 'CHECKED IN';
    }

    const ignored = value => busy || (value === last.code && Date.now() - last.at < IGNORE_SAME_CODE_FOR);

    // Each scan records at once: in if they are out, out if they are in.
    function scan(value) {
        if (ignored(value)) {
            resume();
            return;
        }

        busy = true;
        clearTimeout(timer);
        say('Recording...');
        el('frame').hidden = true;
        el('scanned').hidden = false;
        el('scanned-icon').textContent = 'qr_code_scanner';
        el('scanned-title').textContent = 'QR CODE SCANNED';
        el('scanned-code').textContent = value;

        post(record, {code: value})
            .then(member => {
                last = {code: value, at: Date.now()};
                fill(member);
                say(member.message);
            })
            .catch(error => {
                result.hidden = true;
                say(error.message, false);
            })
            .finally(() => timer = setTimeout(ready, SHOW_FOR));
    }

    el('code-form').addEventListener('submit', event => {
        event.preventDefault();
        const value = el('code').value.trim();
        if (!value || busy) return;
        el('code').value = '';
        // A typed code is deliberate, so it is never ignored as a repeat.
        last = {code: null, at: 0};
        pause();
        scan(value);
    });

    const noCamera = text => {
        scanner = null;
        el('frame').hidden = true;
        el('reader').innerHTML = `<p class="center grey" style="padding: 24px">${text} Type the member code below instead.</p>`;
        say('Camera off', false);
    };

    // The camera is a convenience: if it is missing or blocked, the code field still works.
    if (!window.isSecureContext) {
        // Browsers only hand the camera to https:// pages and localhost.
        noCamera('The camera only works over https:// or on localhost.');
    } else if (typeof Html5Qrcode === 'undefined') {
        noCamera('Scanner library did not load.');
    } else {
        // QR codes only, and the browser's own detector where it has one: both make reads faster.
        scanner = new Html5Qrcode('reader', {
            verbose: false,
            formatsToSupport: typeof Html5QrcodeSupportedFormats === 'undefined' ? undefined : [Html5QrcodeSupportedFormats.QR_CODE],
            experimentalFeatures: {useBarCodeDetectorIfSupported: true},
        });
        // No qrbox: the whole picture is read. The video is cropped to fill the panel,
        // which throws a qrbox off from the frame drawn on screen, so codes near its
        // edge were missed. The frame is just a guide for where to hold the code.
        scanner.start({facingMode: 'environment'}, {fps: 10}, text => {
            text = text.trim();
            // A code still held up after it was recorded: keep the camera running.
            if (ignored(text)) return;
            pause();
            scan(text);
        }).catch(() => noCamera('Camera unavailable or permission denied.'));
    }
</script>
@endpush
