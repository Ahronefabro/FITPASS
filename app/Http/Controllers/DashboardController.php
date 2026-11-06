<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    private const RANGES = [
        'this_week' => 'This Week',
        'last_week' => 'Last Week',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'this_year' => 'This Year',
    ];

    /** Revenue that belongs to no plan, and its chart colour. */
    private const EXTRA_REVENUE = ['Walk-in' => '#9E9E9E', 'Other' => '#616161'];

    public function overview(Request $request)
    {
        // A range picked in the dropdown is remembered in the session, so the
        // Analytics page can show the same one. Without a pick, use the last
        // remembered range, or This Month.
        $picked = $request->query('range');
        if (in_array($picked, array_keys(self::RANGES), true)) {
            session(['dashboard_range' => $picked]);
        }
        $range = $this->currentRange();

        // Work out the date window for the chart.
        $yearly = $range === 'this_year';
        $monthly = str_ends_with($range, 'month');
        [$start, $end] = $this->rangeWindow($range);

        // Week ranges show one bar per day; month ranges one per week; the year one per month.
        $perDay = $this->dailyCounts($start, $end);
        $chart = collect(CarbonPeriod::create($start, $end)->toArray())
            ->groupBy(fn ($day) => $yearly ? $day->format('M') : ($monthly ? 'Week '.(intdiv($day->day - 1, 7) + 1) : $day->format('M j')))
            ->map(fn ($days) => $days->sum(fn ($day) => $perDay[$day->toDateString()] ?? 0));

        // Share of members on each plan, plus the donut chart gradient.
        $colours = Plan::colours();
        $total = max(Member::count(), 1);
        $counts = Member::selectRaw('plan, COUNT(*) as total')->groupBy('plan')->pluck('total', 'plan');
        $byPlan = collect($colours)->map(fn ($colour, $plan) => (int) round(($counts[$plan] ?? 0) / $total * 100));

        $at = 0;
        $stops = $byPlan->map(function ($percent, $plan) use (&$at, $colours) {
            $from = $at;
            $at += $percent;

            return $colours[$plan]." {$from}% {$at}%";
        })->push("#1E1E1E {$at}% 100%")->implode(', ');

        // The revenue card follows the same range as the Check-ins Overview dropdown.
        // (summaryCards() puts the revenue card 4th, so it is index 3.)
        $cards = $this->summaryCards();
        $cards[3] = $this->revenueCard($range, $start, $end, $yearly ? $this->monthlyRevenue($start->year) : null);

        return view('owner.overview', [
            'cards' => $cards,
            'recent' => CheckIn::with('member')->latest('time_in')->take(5)->get(),
            'ranges' => self::RANGES,
            'range' => $range,
            'chart' => $chart,
            'topDays' => CheckIn::selectRaw('DATE(time_in) as day, COUNT(*) as total')
                ->groupBy('day')->orderByDesc('total')->limit(6)->pluck('total', 'day'),
            'byPlan' => $byPlan,
            'colours' => $colours,
            'donut' => "conic-gradient({$stops})",
            'systems' => $this->systemStatus(),
        ]);
    }

    /** The range chosen on the Overview dropdown (remembered in the session), default This Month. */
    private function currentRange(): string
    {
        $range = session('dashboard_range');

        return in_array($range, array_keys(self::RANGES), true) ? $range : 'this_month';
    }

    /** The first and last day covered by a range key. */
    private function rangeWindow(string $range): array
    {
        $yearly = $range === 'this_year';
        $monthly = str_ends_with($range, 'month');
        $start = $yearly ? now()->startOfYear() : ($monthly ? now()->startOfMonth() : now()->startOfWeek());
        if (str_starts_with($range, 'last')) {
            $start = $monthly ? $start->subMonth() : $start->subWeek();
        }
        $end = $yearly ? $start->copy()->endOfYear() : ($monthly ? $start->copy()->endOfMonth() : $start->copy()->endOfWeek());

        return [$start, $end];
    }

    /** The revenue card for the selected range: [title, amount, small line]. */
    private function revenueCard(string $range, $start, $end, $monthlyRevenue = null): array
    {
        // whereDate works whether paid_on is a date or a datetime column.
        $total = Payment::whereDate('paid_on', '>=', $start->toDateString())
            ->whereDate('paid_on', '<=', $end->toDateString())
            ->sum('amount');

        if ($range === 'this_year') {
            // The year shows its best-selling month.
            $top = $monthlyRevenue?->filter()->sortDesc();
            $note = $top && $top->isNotEmpty()
                ? 'Top month: '.$top->keys()->first().' (₱'.number_format($top->first()).')'
                : 'No payments yet';
        } elseif (str_starts_with($range, 'this')) {
            // Current week / month show today's takings.
            $note = '₱'.number_format(Payment::whereDate('paid_on', today())->sum('amount')).' today';
        } else {
            // Past ranges show which dates they cover.
            $note = $start->format('M j').' – '.$end->format('M j');
        }

        return ['Revenue '.self::RANGES[$range], '₱'.number_format($total), $note];
    }

    /** Revenue for each month of a year, Jan to Dec, e.g. ['Jan' => 1200.0, 'Feb' => 0.0, ...]. */
    private function monthlyRevenue(int $year)
    {
        $sums = Payment::whereBetween('paid_on', [
                Carbon::create($year, 1, 1)->toDateString(),
                Carbon::create($year, 12, 31)->toDateString(),
            ])
            ->selectRaw('MONTH(paid_on) as month, SUM(amount) as total')
            ->groupBy('month')->pluck('total', 'month');

        return collect(range(1, 12))->mapWithKeys(
            fn ($month) => [Carbon::create($year, $month, 1)->format('M') => (float) ($sums[$month] ?? 0)]
        );
    }

    /** The earliest year that has any payment or check-in, so the year arrows stop there. */
    private function firstYear(): int
    {
        $dates = array_filter([Payment::min('paid_on'), CheckIn::min('time_in')]);

        return $dates
            ? min(array_map(fn ($date) => Carbon::parse($date)->year, $dates))
            : now()->year;
    }

    /**
     * The System Status panel: each row is [label, what to show, colour class],
     * read from what the system last actually did.
     */
    private function systemStatus(): array
    {
        $lastScan = CheckIn::where('method', 'QR Code')->latest('time_in')->value('time_in');
        $lastPayment = Payment::latest('created_at')->value('created_at');
        $lastCheckIn = CheckIn::latest('time_in')->value('time_in');

        // Recent activity reads green; anything older than a day reads grey.
        $since = fn ($time, string $none) => $time
            ? [Carbon::parse($time)->diffForHumans(), Carbon::parse($time)->gt(now()->subDay()) ? 'green' : 'grey']
            : [$none, 'grey'];

        // The log mailer only writes emails to storage/logs, so nobody receives them.
        $mailer = config('mail.default');
        $mail = $mailer === 'log' || $mailer === 'array'
            ? ['Not sending (log only)', 'orange']
            : ['Sending via '.(config("mail.mailers.{$mailer}.host") ?: $mailer), 'green'];

        return [
            // Reaching this page at all means the database answered.
            ['Database', 'Online', 'green'],
            ['QR Scanner', ...$since($lastScan, 'No scans yet')],
            ['Last check-in', ...$since($lastCheckIn, 'None yet')],
            ['Last payment', ...$since($lastPayment, 'None yet')],
            ['Email', ...$mail],
        ];
    }

    public function analytics(Request $request)
    {
        // Check-ins for the last 7 days.
        $perDay = $this->dailyCounts(today()->subDays(6), now());
        $week = collect(CarbonPeriod::create(today()->subDays(6), today())->toArray())
            ->mapWithKeys(fn ($day) => [$day->format('D') => $perDay[$day->toDateString()] ?? 0]);

        // Revenue share per plan, from the payments taken this month. Walk-ins
        // have a share of their own; a payment whose member was since deleted
        // counts under Other.
        $sums = Payment::query()
            ->leftJoin('members', 'members.id', '=', 'payments.member_id')
            ->where('payments.paid_on', '>=', now()->startOfMonth()->toDateString())
            ->selectRaw("CASE WHEN payments.kind = 'Walk-in' THEN 'Walk-in' ELSE COALESCE(members.plan, 'Other') END as bucket, SUM(payments.amount) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket');
        $revenueTotal = (float) $sums->sum();

        // Every plan shows, even at 0%; Walk-in and Other only when they took money.
        $colours = Plan::colours() + collect(self::EXTRA_REVENUE)->only($sums->keys())->all();
        $revenue = collect($colours)->map(
            fn ($colour, $bucket) => $revenueTotal ? (int) round(($sums[$bucket] ?? 0) / $revenueTotal * 100) : 0
        );

        // Revenue by Month: one year at a time, stepped with the < 2026 > arrows
        // (never before the first data, never into the future).
        $thisYear = now()->year;
        $firstYear = min($this->firstYear(), $thisYear);
        $year = max($firstYear, min((int) $request->query('year', $thisYear), $thisYear));
        $monthlyRevenue = $this->monthlyRevenue($year);

        // The five busiest hours of the last 30 days.
        $hours = CheckIn::where('time_in', '>=', now()->subDays(30))->pluck('time_in')
            ->countBy(fn ($time) => Carbon::parse($time)->hour)->sortDesc()->take(5);
        $busiest = max($hours->max() ?? 0, 1);
        $peak = $hours->map(function ($count, $hour) use ($busiest) {
            $level = $count / $busiest;

            return [
                'time' => Carbon::createFromTime($hour)->format('g:i A').' - '.Carbon::createFromTime(($hour + 1) % 24)->format('g:i A'),
                'level' => $level,
                'label' => $level >= 1 ? 'Peak' : ($level >= .6 ? 'High' : ($level >= .4 ? 'Medium' : ($level >= .2 ? 'Low' : 'Soft'))),
            ];
        })->values();

        // Total members at the end of each of the last 6 months.
        $growth = collect(range(5, 0))->mapWithKeys(function ($monthsAgo) {
            $month = now()->startOfMonth()->subMonths($monthsAgo);

            return [$month->format('M') => Member::whereDate('joined_at', '<=', $month->copy()->endOfMonth())->count()];
        });
        $top = max($growth->max(), 1);
        $points = $growth->values()->map(fn ($count, $i) => [$i * 20, round(90 - $count / $top * 80)]);

        // The revenue card uses the same range as the Overview dropdown.
        $range = $this->currentRange();
        [$rangeStart, $rangeEnd] = $this->rangeWindow($range);
        $cards = $this->summaryCards();
        $cards[3] = $this->revenueCard($range, $rangeStart, $rangeEnd, $range === 'this_year' ? $this->monthlyRevenue($rangeStart->year) : null);

        return view('owner.analytics', [
            'cards' => $cards,
            'week' => $week,
            'revenue' => $revenue,
            'revenueTotal' => $revenueTotal,
            'colours' => $colours,
            'monthlyRevenue' => $monthlyRevenue,
            'topMonth' => $monthlyRevenue->filter()->sortDesc()->keys()->first(),
            'year' => $year,
            'canPrev' => $year > $firstYear,
            'canNext' => $year < $thisYear,
            'peak' => $peak,
            'growth' => $growth,
            'points' => $points,
        ]);
    }

    public function auditLogs(Request $request)
    {
        return view('owner.audit', [
            'tab' => $request->query('tab') === 'reports' ? 'reports' : 'logs',
            'logs' => AuditLog::latest()->simplePaginate(20),
        ]);
    }

    private function dailyCounts($from, $to)
    {
        return CheckIn::whereBetween('time_in', [$from, $to])
            ->selectRaw('DATE(time_in) as day, COUNT(*) as total')
            ->groupBy('day')->pluck('total', 'day');
    }
}