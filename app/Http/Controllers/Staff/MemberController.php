<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index()
    {
        $members = Member::orderBy('name')->simplePaginate(50);

        return view('staff.members', [
            'members' => $members,
            'plans' => array_keys(Member::PLANS),
            'cards' => [
                ['Total Members', Member::count(), Member::whereDate('joined_at', '>=', now()->startOfMonth())->count().' joined this month'],
                ['Active', Member::active()->count(), Member::expiringSoon()->count().' expiring soon'],
                ['Students', Member::where('plan', 'Student')->count(), 'On the student rate'],
                ['Seniors', Member::where('plan', 'Senior')->count(), 'On the senior rate'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        // The staff form only asks for the three fields the Flutter screen had,
        // so the rest of the membership starts on sensible front-desk defaults.
        $member = Member::create($data + [
            'membership_type' => 'Monthly Unlimited',
            'amount' => Member::PRICES[$data['plan']],
            'joined_at' => today(),
            'expires_at' => today()->addMonth(),
        ]);

        AuditLog::record("added member {$member->name}");

        return back()->with('status', "{$member->name} added successfully");
    }

    public function update(Request $request, Member $member)
    {
        $member->update($this->validated($request));
        AuditLog::record("updated member {$member->name}");

        return back()->with('status', "{$member->name} updated successfully");
    }

    public function destroy(Member $member)
    {
        $member->delete();
        AuditLog::record("deleted member {$member->name}");

        return back()->with('status', "{$member->name} deleted successfully");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'plan' => ['required', Rule::in(array_keys(Member::PLANS))],
            'contact' => 'nullable|string|max:32',
        ]);
    }
}
