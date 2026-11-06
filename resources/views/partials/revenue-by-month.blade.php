{{-- Revenue by Month for the owner Analytics page.
     Needs: $monthlyRevenue, $topMonth, $year, $canPrev, $canNext (from DashboardController@analytics). --}}
@php
    $yearTotal = (float) $monthlyRevenue->sum();
    $maxRevenue = max($monthlyRevenue->max(), 1);
@endphp

<div class="grid">
    <section class="panel" id="revenue-by-month">
        <div class="panel-head">
            <h2>Revenue by Month</h2>
            <div class="year-nav" aria-label="Choose year">
                @if ($canPrev)
                    <a href="{{ route('analytics', ['year' => $year - 1]) }}#revenue-by-month" aria-label="Previous year">‹</a>
                @else
                    <span class="is-disabled" aria-hidden="true">‹</span>
                @endif
                <b>{{ $year }}</b>
                @if ($canNext)
                    <a href="{{ route('analytics', ['year' => $year + 1]) }}#revenue-by-month" aria-label="Next year">›</a>
                @else
                    <span class="is-disabled" aria-hidden="true">›</span>
                @endif
            </div>
        </div>

        <div class="rev-stats">
            <span class="rev-chip">Total <b>₱{{ number_format($yearTotal) }}</b></span>
            @if ($topMonth)
                <span class="rev-chip rev-chip--best">Best month <b>{{ $topMonth }} · ₱{{ number_format($monthlyRevenue[$topMonth]) }}</b></span>
            @endif
        </div>

        @if ($yearTotal > 0)
            <div class="bars bars--revenue">
                @foreach ($monthlyRevenue as $month => $amount)
                    <div @class(['top' => $month === $topMonth, 'is-zero' => $amount <= 0])
                         title="{{ $month }} {{ $year }}: ₱{{ number_format($amount) }} ({{ round($amount / $yearTotal * 100) }}% of the year)">
                        <small>₱{{ number_format($amount) }}</small>
                        <i style="height:{{ $amount / $maxRevenue * 100 }}%"></i>
                        <span>{{ $month }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="rev-empty">No payments recorded in {{ $year }}.</p>
        @endif
    </section>
</div>

<style>
    #revenue-by-month { scroll-margin-top: 90px; }

    .year-nav { display: flex; align-items: center; gap: 6px; }
    .year-nav b { min-width: 52px; text-align: center; font-size: 16px; }
    .year-nav a, .year-nav span {
        width: 36px; height: 36px; display: grid; place-items: center;
        background-color: #121212; border: 1px solid #2a2a2a; border-radius: 8px;
        color: #fff; font-size: 20px; line-height: 1; text-decoration: none; user-select: none;
    }
    .year-nav a:hover { border-color: #22c55e; color: #22c55e; }
    .year-nav .is-disabled { opacity: .3; cursor: default; }

    .rev-stats { display: flex; gap: 10px; flex-wrap: wrap; margin: 2px 0 6px; }
    .rev-chip {
        padding: 6px 12px; border: 1px solid #2f343d; border-radius: 999px;
        background: #1a1d24; color: #9ca3af; font-size: 13px;
    }
    .rev-chip b { margin-left: 4px; color: #fff; }
    .rev-chip--best { border-color: rgba(34,197,94,.4); background: rgba(34,197,94,.1); color: #86efac; }
    .rev-chip--best b { color: #22c55e; }

    .bars--revenue > div { cursor: default; }
    .bars--revenue > div i { opacity: .55; transition: opacity .15s; }
    .bars--revenue > div:hover i { opacity: 1; }
    .bars--revenue > div.top i { opacity: 1; box-shadow: 0 0 18px rgba(34,197,94,.35); }
    .bars--revenue > div.top small { color: #22c55e; font-weight: 700; }
    .bars--revenue > div.top span { color: #fff; font-weight: 600; }
    .bars--revenue > div.is-zero small,
    .bars--revenue > div.is-zero span { opacity: .45; }
    .rev-empty { margin: 28px 0 12px; text-align: center; color: #8b93a1; }
</style>