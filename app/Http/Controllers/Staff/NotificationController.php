<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\StaffNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter');
        if (! in_array($filter, StaffNotification::KINDS, true)) {
            $filter = 'All';
        }

        return view('staff.notifications', [
            'filter' => $filter,
            'filters' => array_merge(['All'], StaffNotification::KINDS),
            'notifications' => StaffNotification::query()
                ->when($filter !== 'All', fn ($query) => $query->where('kind', $filter))
                ->latest()->get(),
            'unread' => StaffNotification::unread()->count(),
        ]);
    }

    public function markAllRead()
    {
        StaffNotification::unread()->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read');
    }
}
