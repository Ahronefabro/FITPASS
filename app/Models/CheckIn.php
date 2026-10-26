<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CheckIn extends Model
{
    protected $fillable = ['member_id', 'guest_name', 'time_in', 'time_out', 'method'];

    protected $casts = ['time_in' => 'datetime', 'time_out' => 'datetime'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /** The visit a guest of this name has not checked out of yet, if there is one. */
    public static function openForGuest(string $name): ?self
    {
        return static::whereNull('member_id')->where('guest_name', $name)
            ->whereNull('time_out')->latest('time_in')->first();
    }

    /** Whoever the visit belongs to: a member on file, or a guest typed in by hand. */
    public function getPersonNameAttribute(): string
    {
        return $this->member?->name ?? $this->guest_name ?? 'Guest';
    }

    public function getPlanLabelAttribute(): string
    {
        return $this->member?->plan ?? 'Guest';
    }

    public function getInitialsAttribute(): string
    {
        return Str::of($this->person_name)->explode(' ')->filter()->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    }

    /**
     * One day's attendance as the flat log the staff screens show. A single visit
     * appears twice: once when the member arrived, again once they checked out.
     *
     * @return Collection<int, array{row: CheckIn, action: string, method: string, at: Carbon}>
     */
    public static function logFor(Carbon $day): Collection
    {
        return static::with('member')
            ->where(fn ($query) => $query->whereDate('time_in', $day)->orWhereDate('time_out', $day))
            ->get()
            ->flatMap(function (self $row) use ($day) {
                $events = [];

                if ($row->time_in->isSameDay($day)) {
                    $events[] = ['row' => $row, 'action' => 'Check In', 'method' => $row->method, 'at' => $row->time_in];
                }

                if ($row->time_out?->isSameDay($day)) {
                    $events[] = ['row' => $row, 'action' => 'Check Out', 'method' => $row->method, 'at' => $row->time_out];
                }

                return $events;
            })
            ->sortByDesc('at')
            ->values();
    }
}
