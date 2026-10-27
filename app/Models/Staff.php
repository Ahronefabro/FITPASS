<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Staff extends Model
{
    /** The same names the sign-in uses, so a role can be copied straight to the login. */
    public const ROLES = ['Staff', 'Front Desk', 'Trainers', 'Admin'];

    public const STATUSES = ['Active', 'Inactive', 'Off Duty'];

    protected $table = 'staff';

    protected $fillable = ['user_id', 'name', 'role', 'shift_start', 'status', 'last_login_at'];

    protected $casts = ['last_login_at' => 'datetime'];

    /** The login this row governs. Null only for rows from before they were linked. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getShiftLabelAttribute(): string
    {
        return Carbon::parse($this->shift_start)->format('g:i A');
    }

    public function getInitialsAttribute(): string
    {
        return str($this->name)->explode(' ')->filter()->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    }
}
