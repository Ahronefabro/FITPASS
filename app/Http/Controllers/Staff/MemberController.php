<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index()
    {
        $members = Member::orderBy('name')->simplePaginate(50);

        return view('staff.members', [
            'members' => $members,
            'pending' => Member::pending()->oldest()->get(),
            'plans' => array_keys(Member::PLANS),
            'methods' => Payment::METHODS,
            'prices' => Member::PRICES,
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

    /**
     * An app sign-up paid at the desk: the payment goes on record and the
     * membership starts today.
     */
    public function approve(Request $request, int $pending)
    {
        $member = Member::pending()->findOrFail($pending);
        $data = $request->validate(['method' => ['required', Rule::in(Payment::METHODS)]]);

        DB::transaction(function () use ($member, $data) {
            $member->approve();

            Payment::create([
                'member_id' => $member->id,
                'name' => $member->name,
                'paid_on' => today(),
                'kind' => 'Monthly',
                'amount' => $member->amount,
                'method' => $data['method'],
            ]);
        });

        AuditLog::record("approved app sign-up {$member->name}");

        return back()->with('status', "{$member->name} is now an active member");
    }

    public function reject(int $pending)
    {
        $member = Member::pending()->findOrFail($pending);
        $member->delete();
        AuditLog::record("rejected app sign-up {$member->name}");

        return back()->with('status', "Sign-up from {$member->name} removed");
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
