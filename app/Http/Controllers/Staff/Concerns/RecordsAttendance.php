<?php

namespace App\Http\Controllers\Staff\Concerns;

use App\Models\AuditLog;
use App\Models\CheckIn;
use App\Models\Member;
use Illuminate\Validation\ValidationException;

trait RecordsAttendance
{
    /**
     * Write one time in or time out for a member and return the line to show them.
     * A time in opens a visit; a time out closes the one still open.
     */
    protected function logAttendance(Member $member, string $action, string $method, string $field = 'member_id'): string
    {
        return $this->writeAttendance(
            $member->name, $member->openCheckIn(), ['member_id' => $member->id], $action, $method, $field
        );
    }

    /** The same for a walk-in or first-timer who is not on file: the name is typed in. */
    protected function logGuestAttendance(string $name, string $action, string $method, string $field = 'guest_name'): string
    {
        return $this->writeAttendance(
            $name, CheckIn::openForGuest($name), ['guest_name' => $name], $action, $method, $field
        );
    }

    private function writeAttendance(string $name, ?CheckIn $open, array $owner, string $action, string $method, string $field): string
    {
        if ($action === 'Check In') {
            if ($open) {
                throw ValidationException::withMessages([
                    $field => "{$name} is already checked in.",
                ]);
            }

            CheckIn::create($owner + ['time_in' => now(), 'method' => $method]);
            AuditLog::record("checked in {$name} ({$method})");

            return "{$name} checked in";
        }

        if (! $open) {
            throw ValidationException::withMessages([
                $field => "{$name} is not checked in.",
            ]);
        }

        $open->update(['time_out' => now()]);
        AuditLog::record("checked out {$name} ({$method})");

        return "{$name} checked out";
    }
}
