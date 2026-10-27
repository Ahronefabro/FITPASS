<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The owner's plans and prices. A new price applies to sign-ups from now on;
 * members already on the plan keep what they paid.
 */
class PlanController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validateWithBag('plan', [
            'plan_name' => ['required', 'string', 'max:50', Rule::unique('plans', 'name')],
            'price' => 'required|numeric|min:0|max:1000000',
            'colour' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], [], ['plan_name' => 'plan name']);

        $plan = Plan::create(['name' => $data['plan_name'], 'price' => $data['price'], 'colour' => $data['colour']]);
        AuditLog::record("added the {$plan->name} plan at ₱".number_format($plan->price, 2));

        return back()->with('status', "{$plan->name} plan added");
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $request->validateWithBag('plan', [
            'price' => 'required|numeric|min:0|max:1000000',
            'colour' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $old = $plan->price;
        $plan->update($data);
        AuditLog::record("set the {$plan->name} plan to ₱".number_format($plan->price, 2).' (was ₱'.number_format($old, 2).')');

        return back()->with('status', "{$plan->name} plan saved");
    }

    public function destroy(Plan $plan)
    {
        // Members keep the plan's name; deleting a plan in use would strand them.
        abort_if(Member::withPending()->where('plan', $plan->name)->exists(), 422, 'Members are still on this plan.');

        $plan->delete();
        AuditLog::record("deleted the {$plan->name} plan");

        return back()->with('status', "{$plan->name} plan deleted");
    }
}
