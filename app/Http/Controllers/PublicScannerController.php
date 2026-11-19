<?php

namespace App\Http\Controllers;

use App\Auth\Portal;
use App\Http\Controllers\Staff\ScannerController;
use Illuminate\View\View;

/**
 * The scanner on the front page, open without signing in. It is the staff
 * scanner with a different way in and out: each scan goes through the same
 * code (store() is the staff scanner's own), onto the same check-ins and audit log, so staff and the owner see it
 * in their portals as if it had been scanned there.
 */
class PublicScannerController extends ScannerController
{
    public function index(): View
    {
        $user = auth()->user();
        $portal = Portal::forRole($user?->role);

        return view('staff.scanner', [
            'recordUrl' => route('scan.record'),
            // Signed in: back to their own portal. Otherwise: the sign-in.
            'backUrl' => $portal ? route(Portal::homeRoute($portal)) : route('login'),
            'backLabel' => match ($portal) {
                Portal::OWNER => 'Owner portal',
                Portal::STAFF => 'Staff portal',
                default => 'Staff / Owner login',
            },
            'backIcon' => $portal ? 'arrow_back' : 'login',
        ]);
    }
}
