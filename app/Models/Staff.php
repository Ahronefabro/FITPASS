<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Staff extends Model
{
    public const ROLES = ['Staff', 'Front Desk', 'Trainers', 'Admins'];

    public const STATUSES = ['Active', 'Inactive', 'Off Duty'];

    protected $table = 'staff';

    protected $fillable = ['name', 'role', 'shift_start', 'status', 'last_login_at'];

    protected $casts = ['last_login_at' => 'datetime'];

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