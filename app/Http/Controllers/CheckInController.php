<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CheckIn;
use App\Models\Member;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'All');

        $today = CheckIn::whereDate('time_in', today())->count();
        $yesterday = CheckIn::whereDate('time_in', today()->subDay())->count();
        $change = $yesterday ? round(($today - $yesterday) / $yesterday * 100) : null;

        return view('owner.checkins', [
            'filter' => $filter,
            'logs' => CheckIn::with('member')->whereDate('time_in', today())
                ->when($filter === 'QR-code', fn ($q) => $q->where('method', 'QR Code'))
                ->when($filter === 'Manual', fn ($q) => $q->where('method', 'Manual'))
                ->when($filter === 'Currently In', fn ($q) => $q->whereNull('time_out'))
                ->latest('time_in')->get(),
            'members' => Member::orderBy('name')->pluck('name', 'id'),
            'cards' => [
                ['Check-ins Today', $today, $change === null ? 'No check-ins yesterday' : ($change >= 0 ? '↑ ' : '↓ ').abs($change).'% vs yesterday', $change < 0],
                ['Check-outs Today', CheckIn::whereDate('time_out', today())->count(), 'Completed visits'],
                ['Still in Gym', CheckIn::whereDate('time_in', today())->whereNull('time_out')->count(), 'Not checked out yet'],
                ['Manual Entries', CheckIn::whereDate('time_in', today())->where('method', 'Manual')->count(), 'Today'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['member_id' => 'required|exists:members,id']);
        $member = Member::find($data['member_id']);

        $alreadyIn = CheckIn::where('member_id', $member->id)
            ->whereDate('time_in', today())->whereNull('time_out')->exists();

        if ($alreadyIn) {
            return back()->withErrors(['member_id' => "{$member->name} is already checked in."]);
        }

        CheckIn::create(['member_id' => $member->id, 'time_in' => now(), 'method' => 'Manual']);
        AuditLog::record("manually checked in {$member->name}");

        return back()->with('status', "{$member->name} checked in");
    }

    public function checkout(CheckIn $checkIn)
    {
        $checkIn->update(['time_out' => $checkIn->time_out ?? now()]);
        AuditLog::record("checked out {$checkIn->person_name}");

        return back()->with('status', "{$checkIn->person_name} checked out");
    }

    public function export(Request $request)
    {
        $logs = CheckIn::with('member')
            ->when(! $request->boolean('all'), fn ($q) => $q->whereDate('time_in', today()))
            ->oldest('time_in')->get();

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Member', 'Plan', 'Time in', 'Time out', 'Method', 'Status'], ',', '"', '\\');

            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->person_name,
                    $log->plan_label,
                    $log->time_in->format('Y-m-d g:i A'),
                    $log->time_out?->format('Y-m-d g:i A') ?? '',
                    $log->method,
                    $log->time_out ? 'Checked Out' : 'In Gym',
                ], ',', '"', '\\');
            }

            fclose($out);
        }, 'check-ins-'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }
}