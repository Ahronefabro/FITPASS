<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Member;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    private const RANGES = [
        'this_week' => 'This Week',
        'last_week' => 'Last Week',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
    ];

    public function index(Request $request)
    {
        $range = $request->query('range');
        if (! in_array($range, array_keys(self::RANGES), true)) {
            $range = 'this_week';
        }

        // Work out the chart window: week ranges plot days, month ranges plot weeks.
        $monthly = str_ends_with($range, 'month');
        $start = $monthly ? now()->startOfMonth() : now()->startOfWeek();
        if (str_starts_with($range, 'last')) {
            $start = $monthly ? $start->subMonth() : $start->subWeek();
        }
        $end = $monthly ? $start->copy()->endOfMonth() : $start->copy()->endOfWeek();

        $days = collect(CarbonPeriod::create($start, $end)->toArray());
        $label = fn ($day) => $monthly ? 'Week '.(intdiv($day->day - 1, 7) + 1) : $day->format('M j');

        $ins = $this->series($days, $label, $this->dailyCounts('time_in', $start, $end));
        $outs = $this->series($days, $label, $this->dailyCounts('time_out', $start, $end));
        $peak = max($ins->max(), $outs->max(), 1);

        $inToday = CheckIn::whereDate('time_in', today())->count();
        $inBefore = CheckIn::whereDate('time_in', today()->subDay())->count();
        $outToday = CheckIn::whereDate('time_out', today())->count();
        $outBefore = CheckIn::whereDate('time_out', today()->subDay())->count();

        $log = CheckIn::logFor(today());

        return view('staff.dashboard', [
            'cards' => [
                ['Check-ins Today', $inToday, $this->change($inToday, $inBefore), $inToday < $inBefore],
                ['Check-outs Today', $outToday, $this->change($outToday, $outBefore), $outToday < $outBefore],
                ['Active Members', Member::active()->count(), Member::expiringSoon()->count().' expiring soon'],
            ],
            'recent' => $log->take(5),
            'ranges' => self::RANGES,
            'range' => $range,
            'labels' => $ins->keys(),
            'inPoints' => $this->points($ins, $peak),
            'outPoints' => $this->points($outs, $peak),
            'peak' => $peak,
            'totals' => [
                'Total Check-Ins' => $log->where('action', 'Check In')->count(),
                'Total Check-Outs' => $log->where('action', 'Check Out')->count(),
                'Manual Entries' => $log->where('method', 'Manual')->count(),
                'Active Members' => Member::active()->count(),
            ],
        ]);
    }

    /** Check-ins or check-outs per calendar day, keyed by date. */
    private function dailyCounts(string $column, $from, $to): Collection
    {
        abort_unless(in_array($column, ['time_in', 'time_out'], true), 500);

        return CheckIn::whereBetween($column, [$from, $to])
            ->selectRaw("DATE({$column}) as day, COUNT(*) as total")
            ->groupBy('day')->pluck('total', 'day');
    }

    /** Roll the daily counts up into the buckets the chart draws. */
    private function series(Collection $days, callable $label, Collection $counts): Collection
    {
        return $days->groupBy($label)
            ->map(fn ($bucket) => $bucket->sum(fn ($day) => $counts[$day->toDateString()] ?? 0));
    }

    /** A polyline across a 100x100 box, so the chart stretches to any width. */
    private function points(Collection $series, int $peak): string
    {
        $last = max($series->count() - 1, 1);

        return $series->values()
            ->map(fn ($value, $i) => round($i / $last * 100, 2).','.round(100 - $value / $peak * 100, 2))
            ->implode(' ');
    }

    private function change(int $now, int $before): string
    {
        if (! $before) {
            return $now ? 'First records today' : 'Nothing logged yet';
        }

        $percent = round(($now - $before) / $before * 100);

        return ($percent >= 0 ? '↑ ' : '↓ ').abs($percent).'% vs yesterday';
    }
}
