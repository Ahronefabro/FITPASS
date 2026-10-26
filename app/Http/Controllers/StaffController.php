<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'All');

        return view('owner.staff', [
            'filter' => $filter,
            'staff' => Staff::query()
                ->when(in_array($filter, Staff::ROLES, true), fn ($q) => $q->where('role', $filter))
                ->orderBy('name')->get(),
            'roles' => Staff::ROLES,
            'statuses' => Staff::STATUSES,
            'cards' => [
                ['Total Staff', Staff::count(), Staff::where('created_at', '>=', now()->startOfMonth())->count().' added this month'],
                ['Active', Staff::where('status', 'Active')->count(), 'On shift now'],
                ['Off Duty', Staff::where('status', 'Off Duty')->count(), 'Not on shift', true],
                ['Admins', Staff::where('role', 'Admins')->count(), 'Full access'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'role' => ['required', Rule::in(Staff::ROLES)],
            'shift_start' => 'required|date_format:H:i',
            'status' => ['required', Rule::in(Staff::STATUSES)],
        ]);

        $staff = Staff::create($data);
        AuditLog::record("added staff account {$staff->name}");

        return back()->with('status', "{$staff->name} was added");
    }

    public function update(Request $request, Staff $staff)
    {
        $data = $request->validate([
            'role' => ['sometimes', Rule::in(Staff::ROLES)],
            'status' => ['sometimes', Rule::in(Staff::STATUSES)],
        ]);
        abort_if(empty($data), 422);

        $staff->update($data);
        $change = implode(', ', $data);
        AuditLog::record("set staff account {$staff->name} to {$change}");

        return back()->with('status', "{$staff->name} is now {$change}");
    }

    public function destroy(Staff $staff)
    {
        $staff->delete();
        AuditLog::record("deleted staff account {$staff->name}");

        return back()->with('status', "{$staff->name} was deleted");
    }
}