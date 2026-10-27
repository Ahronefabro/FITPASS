<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Staff;
use App\Models\StaffNotification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // A login for each portal. Change the passwords before going live.
        $this->account('owner@fitpass.test', 'Super Admin', 'admin', 'Owner');
        $staff = $this->account('staff@fitpass.test', 'Front Desk Staff', 'staff', 'Staff');

        Staff::firstOrCreate(
            ['user_id' => $staff->id],
            ['name' => $staff->name, 'role' => 'Staff', 'shift_start' => '06:00', 'status' => 'Active'],
        );

        // Members created before the staff screens existed have no contact or code.
        $this->backfill();

        if (! StaffNotification::exists()) {
            $this->notifications();
        }
    }

    private function account(string $email, string $name, string $username, string $role): User
    {
        return tap(User::firstOrNew(['email' => $email])->forceFill([
            'name' => $name,
            'username' => $username,
            'role' => $role,
            'password' => Hash::make('password'),
        ]))->save();
    }

    private function backfill(): void
    {
        $missing = Member::whereNull('contact')->orWhereNull('qr_code')->get();

        foreach ($missing as $member) {
            $member->forceFill([
                'contact' => $member->contact ?? '09'.str_pad((string) $member->id, 9, '0', STR_PAD_LEFT),
                'qr_code' => $member->qr_code ?? Member::newCode(),
            ])->save();
        }
    }

    /** From the Flutter notifications screen. */
    private function notifications(): void
    {
        // title, body, kind, level, hours ago
        $rows = [
            ['Unusual check-in pattern', 'Jaspher Sibayan checked in twice within 5 minutes at the front desk.', 'QR-code', 'success', 3],
            ['Membership expiring soon', 'Dwayne Tore is on a plan that ends in 3 days.', 'Manual', 'warning', 6],
            ['Membership expired', 'Emman Jalal expired and has not been renewed.', 'Manual', 'danger', 26],
            ['New member checked in for the first time', 'Fabs Dela Cruz completed a first check-in using a QR code.', 'QR-code', 'success', 48],
            ['Backup complete', 'Nightly database backup finished successfully.', 'Manual', 'info', 50],
            ['Daily report ready', 'The check-in and revenue summary for yesterday has been generated.', 'Manual', 'info', 72],
            ['3 members expiring next week', 'Review the Members page to send renewal reminders.', 'Manual', 'warning', 74],
        ];

        foreach ($rows as [$title, $body, $kind, $level, $hoursAgo]) {
            $notification = new StaffNotification([
                'title' => $title,
                'body' => $body,
                'kind' => $kind,
                'level' => $level,
            ]);

            // Keep the "6 hours ago" style timestamps the Flutter screen showed.
            $notification->created_at = now()->subHours($hoursAgo);
            $notification->updated_at = $notification->created_at;
            $notification->save();
        }
    }
}
