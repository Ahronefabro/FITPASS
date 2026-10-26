<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Member extends Model
{
    // Plan name => chart colour
    public const PLANS = ['Regular' => '#00FF66', 'Student' => '#40C4FF', 'Senior' => '#2196F3'];

    public const TYPES = ['Monthly Unlimited', 'Quarterly'];

    /** What each plan costs, used when staff sign someone up at the front desk. */
    public const PRICES = ['Regular' => 1000, 'Student' => 800, 'Senior' => 700];

    protected $fillable = ['name', 'plan', 'membership_type', 'contact', 'qr_code', 'amount', 'joined_at', 'expires_at'];

    protected $casts = ['joined_at' => 'date', 'expires_at' => 'date'];

    /** Every member needs a code the scanner can read. */
    protected static function booted(): void
    {
        static::creating(function (self $member) {
            $member->qr_code ??= static::newCode();
        });
    }

    public static function newCode(): string
    {
        return 'FP-'.mb_strtoupper(Str::random(8));
    }

    public function checkIns()
    {
        return $this->hasMany(CheckIn::class);
    }

    /** The visit this member has not checked out of yet, if there is one. */
    public function openCheckIn(): ?CheckIn
    {
        return $this->checkIns()->whereNull('time_out')->latest('time_in')->first();
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereDate('expires_at', '>=', today());
    }

    public function scopeExpiringSoon(Builder $query): void
    {
        $query->whereDate('expires_at', '>=', today())
            ->whereDate('expires_at', '<=', today()->addDays(7));
    }

    public function getVisitsThisMonthAttribute(): int
    {
        return $this->checkIns()->where('time_in', '>=', now()->startOfMonth())->count();
    }

    public function getStatusAttribute(): string
    {
        if ($this->expires_at->lt(today())) {
            return 'Expired';
        }

        return $this->expires_at->lte(today()->addDays(7)) ? 'Expiring soon' : 'Active';
    }

    public function getInitialsAttribute(): string
    {
        return str($this->name)->explode(' ')->filter()->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    }
}
