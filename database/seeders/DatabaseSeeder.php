<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Payment;
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

        $fresh = ! Member::exists();

        if ($fresh) {
            $this->members();
        }

        // Members created before the staff screens existed have no contact or code.
        $this->backfill();

        if (! Payment::exists()) {
            $this->payments();
        }

        if (! StaffNotification::exists()) {
            $this->notifications();
        }

        if ($fresh) {
            AuditLog::record('loaded the sample data', 'System');
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

    /** Sample data from the Flutter screens. Delete this for an empty system. */
    private function members(): void
    {
        // name, plan, type, amount paid, joined (days ago), expires (days from now)
        $people = [
            ['Jaspher Sibayan', 'Regular', 'Monthly Unlimited', 1000, 20, 10],
            ['Ahrone Magtibay', 'Student', 'Quarterly', 800, 60, 30],
            ['Emman Jalal', 'Regular', 'Monthly Unlimited', 1000, 45, -15],
            ['Fabs Dela Cruz', 'Senior', 'Monthly Unlimited', 700, 10, 20],
            ['Dwayne Tore', 'Regular', 'Monthly Unlimited', 1000, 27, 3],
            ['Emman Pico', 'Student', 'Monthly Unlimited', 800, 40, -10],
        ];

        foreach ($people as $i => $person) {
            $member = Member::create([
                'name' => $person[0],
                'plan' => $person[1],
                'membership_type' => $person[2],
                'amount' => $person[3],
                'joined_at' => today()->subDays($person[4]),
                'expires_at' => today()->addDays($person[5]),
            ]);

            // A visit every other day for two weeks, so the charts have something to show.
            foreach (range(0, 13) as $daysAgo) {
                if (($daysAgo + $i) % 2 !== 0) {
                    continue;
                }

                $in = now()->subDays($daysAgo)->subMinutes(20 + $i * 45);

                CheckIn::create([
                    'member_id' => $member->id,
                    'time_in' => $in,
                    'time_out' => $daysAgo === 0 && $i === 0 ? null : $in->copy()->addMinutes(15),
                    'method' => $i % 2 ? 'QR Code' : 'Manual',
                ]);
            }
        }
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

    /** From the Flutter payments screen: monthly dues over GCash, walk-ins in cash. */
    private function payments(): void
    {
        // name, days ago, walk-in or monthly, amount, method
        $rows = [
            ['Jaspher Sibayan', 3, 'Monthly', 700, 'Gcash'],
            ['Emman Jalal', 6, 'Monthly', 700, 'Gcash'],
            ['Michael Jackson', 6, 'Walk-in', 80, 'Cash'],
            ['Fabs Dela Cruz', 7, 'Monthly', 700, 'Gcash'],
            ['Juan Tamad', 8, 'Walk-in', 80, 'Cash'],
            ['Chris Bumstead', 8, 'Walk-in', 80, 'Cash'],
            ['Dwayne Tore', 9, 'Monthly', 700, 'Gcash'],
            ['Ahrone Magtibay', 11, 'Monthly', 700, 'Gcash'],
            ['Emman Pico', 11, 'Monthly', 700, 'Gcash'],
        ];

        $members = Member::pluck('id', 'name');

        foreach ($rows as [$name, $daysAgo, $kind, $amount, $method]) {
            Payment::create([
                'member_id' => $members[$name] ?? null,
                'name' => $name,
                'paid_on' => today()->subDays($daysAgo),
                'kind' => $kind,
                'amount' => $amount,
                'method' => $method,
            ]);
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
