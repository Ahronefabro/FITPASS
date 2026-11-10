@extends('staff.layout')
@section('title', 'Payments')

@section('content')
    <h1>Payments</h1>

    <section class="panel alt">
        <h2>Payment Records</h2>
        <div class="toolbar">
            @foreach ($filters as $option)
                <a href="{{ route('staff.payments', ['filter' => $option]) }}" @class(['chip', 'active' => $filter === $option])>{{ $option }}</a>
            @endforeach
        </div>

        <div class="table-wrap">
            <table id="paymentsTable">
                <tr><th>Name</th><th>Date</th><th>Walk-in/Monthly</th><th>Amount</th><th>Method</th></tr>
                @forelse ($payments as $payment)
                    <tr class="payment-row">
                        <td>{{ $payment->name }}</td>
                        <td>{{ $payment->paid_on->format('M j, Y') }}</td>
                        <td>{{ $payment->kind }}</td>
                        <td>₱{{ number_format($payment->amount, 2) }}</td>
                        <td><span @class(['badge', $payment->method === 'Gcash' ? 'sky' : 'green'])>{{ $payment->method }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5">No payments found.</td></tr>
                @endforelse
            </table>
        </div>

        @if (count($payments))
            <div class="pager">
                <span class="pager__info" id="paymentsInfo"></span>
                <nav class="pager__nav" id="paymentsPager" aria-label="Payments pages"></nav>
            </div>
        @endif
    </section>

    <style>
        /* Pagination */
        .pager { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: 16px; }
        .pager__info { font-size: 13px; color: #8b93a1; }
        .pager__nav { display: flex; align-items: center; gap: 6px; }
        .pager .pager__btn {
            min-width: 36px; height: 36px; padding: 0 10px; display: grid; place-items: center;
            border: 1px solid #343944; border-radius: 10px; background: #1a1d24;
            color: #e5e7eb; font-size: 14px; font-weight: 600; cursor: pointer;
            transition: border-color .15s, color .15s, background .15s;
        }
        .pager .pager__btn:hover:not(:disabled):not([aria-current="page"]) { border-color: #22c55e; color: #22c55e; }
        .pager .pager__btn[aria-current="page"] { background: #22c55e; border-color: #22c55e; color: #05260f; cursor: default; }
        .pager .pager__btn:disabled { opacity: .4; cursor: default; }
        .pager .pager__btn:focus-visible { outline: 0; box-shadow: 0 0 0 3px rgba(34,197,94,.25); }
        .pager__gap { padding: 0 4px; color: #6b7280; }
    </style>

    <script>
    // Client-side pagination for the Payment Records table.
    (() => {
        const PAGE_SIZE = 10;
        const rows = [...document.querySelectorAll('#paymentsTable .payment-row')];
        const nav = document.getElementById('paymentsPager');
        const info = document.getElementById('paymentsInfo');
        if (!rows.length || !nav || !info) return;

        const totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
        let page = 1;

        // 1 … 4 5 6 … 12
        function pageItems(current, total) {
            if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
            const keep = [...new Set([1, total, current - 1, current, current + 1])]
                .filter(n => n >= 1 && n <= total)
                .sort((a, b) => a - b);
            const out = [];
            let prev = 0;
            keep.forEach(n => {
                if (n - prev > 1) out.push('…');
                out.push(n);
                prev = n;
            });
            return out;
        }

        function button(label, { disabled = false, current = false, aria, onClick } = {}) {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'pager__btn';
            b.textContent = label;
            if (aria) b.setAttribute('aria-label', aria);
            if (current) b.setAttribute('aria-current', 'page');
            b.disabled = disabled;
            if (onClick) b.addEventListener('click', onClick);
            return b;
        }

        function go(n) {
            page = Math.min(Math.max(1, n), totalPages);
            render();
        }

        function render() {
            const start = (page - 1) * PAGE_SIZE;
            const end = Math.min(start + PAGE_SIZE, rows.length);

            rows.forEach((row, i) => { row.style.display = (i >= start && i < end) ? '' : 'none'; });
            info.textContent = `Showing ${start + 1}–${end} of ${rows.length}`;

            nav.innerHTML = '';
            nav.append(button('‹', { disabled: page === 1, aria: 'Previous page', onClick: () => go(page - 1) }));
            pageItems(page, totalPages).forEach(item => {
                if (item === '…') {
                    const gap = document.createElement('span');
                    gap.className = 'pager__gap';
                    gap.textContent = '…';
                    nav.append(gap);
                } else {
                    nav.append(button(String(item), {
                        current: item === page,
                        aria: `Page ${item}`,
                        onClick: () => go(item),
                    }));
                }
            });
            nav.append(button('›', { disabled: page === totalPages, aria: 'Next page', onClick: () => go(page + 1) }));
        }

        render();
    })();
    </script>
@endsection