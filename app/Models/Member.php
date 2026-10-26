<?php

namespace App\Models;

use App\Notifications\MemberResetPassword;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/**
 * Someone with a membership. Most are added at the front desk; others sign up
 * in the mobile app and log in there with an email and password.
 *
 * An app sign-up stays pending, with no dates, until staff confirm the payment.
 * Pending sign-ups are left out of every query by default so counts, lists and
 * the scanner only ever see real memberships. Reach them through pending() or
 * withPending().
 */
class Member extends Authenticatable
{
    use HasApiTokens, Notifiable;

    // Plan name => chart colour
    public const PLANS = ['Regular' => '#00FF66', 'Student' => '#40C4FF', 'Senior' => '#2196F3'];

    public const TYPES = ['Monthly Unlimited', 'Quarterly'];

    /** What each plan costs, used when staff sign someone up at the front desk. */
    public const PRICES = ['Regular' => 1000, 'Student' => 800, 'Senior' => 700];

    protected $fillable = ['name', 'plan', 'membership_type', 'contact', 'email', 'password', 'qr_code', 'amount', 'joined_at', 'expires_at'];

    protected $hidden = ['password'];

    protected $casts = ['joined_at' => 'date', 'expires_at' => 'date', 'password' => 'hashed'];

    protected static function booted(): void
    {
        static::addGlobalScope('approved', fn (Builder $query) => $query->whereNotNull('expires_at'));

        // Every member needs a code the scanner can read.
        static::creating(function (self $member) {
            $member->qr_code ??= static::newCode();
        });
    }

    public static function newCode(): string
    {
        return 'FP-'.mb_strtoupper(Str::random(8));
    }

    /** Every member, pending sign-ups included. */
    public static function withPending(): Builder
    {
        return static::withoutGlobalScope('approved');
    }

    /** App sign-ups still waiting for staff to confirm the payment. */
    public static function pending(): Builder
    {
        return static::withPending()->whereNull('expires_at');
    }

    public function isPending(): bool
    {
        return $this->expires_at === null;
    }

    /**
     * Staff took the first payment: the membership starts today and the code
     * works on the scanner from now on.
     */
    public function approve(): void
    {
        $this->update([
            'amount' => self::PRICES[$this->plan],
            'joined_at' => today(),
            'expires_at' => today()->addMonth(),
        ]);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new MemberResetPassword($token));
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
        if ($this->isPending()) {
            return 'Pending';
        }

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

    /** What the mobile app shows about the member and their membership. */
    public function toApp(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'contact' => $this->contact,
            'plan' => $this->plan,
            'membership_type' => $this->membership_type,
            'member_id' => 'ID #'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT),
            'status' => $this->status,
            // A pending code would only be turned away at the scanner.
            'qr_code' => $this->isPending() ? null : $this->qr_code,
            'joined_at' => $this->joined_at?->toDateString(),
            'expires_at' => $this->expires_at?->toDateString(),
            'visits_this_month' => $this->visits_this_month,
        ];
    }
}
