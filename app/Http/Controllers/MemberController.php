<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'All');

        $members = Member::query()
            ->when(in_array($filter, array_keys(Member::PLANS), true), fn ($q) => $q->where('plan', $filter))
            ->when($filter === 'Expiring soon', fn ($q) => $q->expiringSoon())
            ->orderBy('name')
            ->simplePaginate(50)
            ->withQueryString();

        return view('owner.members', [
            'cards' => $this->summaryCards(),
            'filter' => $filter,
            'view' => $request->query('view') === 'grid' ? 'grid' : 'table',
            'members' => $members,
            'plans' => array_keys(Member::PLANS),
            'types' => Member::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'plan' => ['required', Rule::in(array_keys(Member::PLANS))],
            'membership_type' => ['required', Rule::in(Member::TYPES)],
            'amount' => 'required|numeric|min:0',
            'joined_at' => 'required|date',
            'expires_at' => 'required|date|after_or_equal:joined_at',
        ]);

        $member = Member::create($data);
        AuditLog::record("added new member {$member->name}");

        return back()->with('status', "{$member->name} was added");
    }
}