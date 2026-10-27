<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * The owner's staff directory. Each row belongs to one login: adding a row
 * creates the login, its role is the login's role, and deleting the row
 * deletes the login.
 */
class StaffController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'All');

        return view('owner.staff', [
            'filter' => $filter,
            'staff' => Staff::query()
                ->with('user:id,username')
                ->when(in_array($filter, Staff::ROLES, true), fn ($q) => $q->where('role', $filter))
                ->orderBy('name')->get(),
            'roles' => Staff::ROLES,
            'statuses' => Staff::STATUSES,
            'cards' => [
                ['Total Staff', Staff::count(), Staff::where('created_at', '>=', now()->startOfMonth())->count().' added this month'],
                ['Active', Staff::where('status', 'Active')->count(), 'On shift now'],
                ['Off Duty', Staff::where('status', 'Off Duty')->count(), 'Not on shift', true],
                ['Admins', Staff::where('role', 'Admin')->count(), 'Full access'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', Password::min(8)],
            'role' => ['required', Rule::in(Staff::ROLES)],
            'shift_start' => 'required|date_format:H:i',
            'status' => ['required', Rule::in(Staff::STATUSES)],
        ]);

        $staff = DB::transaction(function () use ($data) {
            // The model's 'hashed' cast hashes the password.
            $user = User::forceCreate([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'role' => $data['role'],
                'password' => $data['password'],
            ]);

            return $user->staff()->create([
                'name' => $data['name'],
                'role' => $data['role'],
                'shift_start' => $data['shift_start'],
                'status' => $data['status'],
            ]);
        });

        AuditLog::record("added staff account {$staff->name} ({$data['username']})");

        return back()->with('status', "{$staff->name} was added and can sign in as {$data['username']}");
    }

    public function update(Request $request, Staff $staff)
    {
        $data = $request->validate([
            'role' => ['sometimes', Rule::in(Staff::ROLES)],
            'status' => ['sometimes', Rule::in(Staff::STATUSES)],
        ]);
        abort_if(empty($data), 422);
        $this->guardOwnerLogin($staff);

        DB::transaction(function () use ($staff, $data) {
            $staff->update($data);

            // The login's role is what opens a portal, so it follows straight away.
            if (isset($data['role'])) {
                $staff->user?->forceFill(['role' => $data['role']])->save();
            }
        });

        $change = implode(', ', $data);
        AuditLog::record("set staff account {$staff->name} to {$change}");

        return back()->with('status', "{$staff->name} is now {$change}");
    }

    public function destroy(Staff $staff)
    {
        $this->guardOwnerLogin($staff);

        // Deleting the login takes the row with it. A row with no login is just
        // the row.
        DB::transaction(fn () => $staff->user ? $staff->user->delete() : $staff->delete());
        AuditLog::record("deleted staff account {$staff->name}");

        return back()->with('status', "{$staff->name} was deleted");
    }

    /**
     * The directory must never change or remove the owner's own login, or
     * whoever is signed in, which could lock everyone out of the owner portal.
     */
    private function guardOwnerLogin(Staff $staff): void
    {
        abort_if(
            $staff->user && ($staff->user->role === 'Owner' || $staff->user->is(auth()->user())),
            403,
            'This account cannot be changed from the staff directory.',
        );
    }
}
