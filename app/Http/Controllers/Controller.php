<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Payment;

abstract class Controller
{
    /** The four headline cards shared by Overview, Analytics and Members. */
    protected function summaryCards(): array
    {
        return [
            ['Total Members', number_format(Member::count()), Member::whereDate('joined_at', '>=', now()->startOfMonth())->count().' joined this month'],
            ['Total Check-ins', number_format(CheckIn::count()), CheckIn::whereDate('time_in', today())->count().' today'],
            ['Active Members', number_format(Member::active()->count()), Member::expiringSoon()->count().' expiring soon'],
            // Money actually taken, from the payments staff record: dues, renewals and walk-ins.
            ['Revenue This Month', '₱'.number_format(Payment::where('paid_on', '>=', now()->startOfMonth()->toDateString())->sum('amount')),
                '₱'.number_format(Payment::whereDate('paid_on', today())->sum('amount')).' today'],
        ];
    }
}