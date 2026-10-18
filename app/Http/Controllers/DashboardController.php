<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CheckIn;
use App\Models\Member;
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
    ];

    public function overview(Request $request)
    {
        $range = $request->query('range');
        if (! in_array($range, array_keys(self::RANGES), true)) {
            $range = 'this_week';
        }

        // Work out the date window for the chart.
        $monthly = str_ends_with($range, 'month');
        $start = $monthly ? now()->startOfMonth() : now()->startOfWeek();
        if (str_starts_with($range, 'last')) {
            $start = $monthly ? $start->subMonth() : $start->subWeek();
        }
        $end = $monthly ? $start->copy()->endOfMonth() : $start->copy()->endOfWeek();

        // Week ranges show one bar per day; month ranges show one bar per week.
        $perDay = $this->dailyCounts($start, $end);
        $chart = collect(CarbonPeriod::create($start, $end)->toArray())
            ->groupBy(fn ($day) => $monthly ? 'Week '.(intdiv($day->day - 1, 7) + 1) : $day->format('M j'))
            ->map(fn ($days) => $days->sum(fn ($day) => $perDay[$day->toDateString()] ?? 0));

        // Share of members on each plan, plus the donut chart gradient.
        $total = max(Member::count(), 1);
        $counts = Member::selectRaw('plan, COUNT(*) as total')->groupBy('plan')->pluck('total', 'plan');
        $byPlan = collect(Member::PLANS)->map(fn ($colour, $plan) => (int) round(($counts[$plan] ?? 0) / $total * 100));

        $at = 0;
        $stops = $byPlan->map(function ($percent, $plan) use (&$at) {
            $from = $at;
            $at += $percent;

            return Member::PLANS[$plan]." {$from}% {$at}%";
        })->push("#1E1E1E {$at}% 100%")->implode(', ');

        return view('owner.overview', [
            'cards' => $this->summaryCards(),
            'recent' => CheckIn::with('member')->latest('time_in')->take(5)->get(),
            'ranges' => self::RANGES,
            'range' => $range,
            'chart' => $chart,
            'topDays' => CheckIn::selectRaw('DATE(time_in) as day, COUNT(*) as total')
                ->groupBy('day')->orderByDesc('total')->limit(6)->pluck('total', 'day'),
            'byPlan' => $byPlan,
            'colours' => Member::PLANS,
            'donut' => "conic-gradient({$stops})",
        ]);
    }

    public function analytics()
    {
        // Check-ins for the last 7 days.
        $perDay = $this->dailyCounts(today()->subDays(6), now());
        $week = collect(CarbonPeriod::create(today()->subDays(6), today())->toArray())
            ->mapWithKeys(fn ($day) => [$day->format('D') => $perDay[$day->toDateString()] ?? 0]);

        // Revenue share per plan, from active memberships.
        $sums = Member::active()->selectRaw('plan, SUM(amount) as total')->groupBy('plan')->pluck('total', 'plan');
        $revenueTotal = (float) $sums->sum();
        $revenue = collect(Member::PLANS)->map(
            fn ($colour, $plan) => $revenueTotal ? (int) round(($sums[$plan] ?? 0) / $revenueTotal * 100) : 0
        );

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

        return view('owner.analytics', [
            'cards' => $this->summaryCards(),
            'week' => $week,
            'revenue' => $revenue,
            'revenueTotal' => $revenueTotal,
            'colours' => Member::PLANS,
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