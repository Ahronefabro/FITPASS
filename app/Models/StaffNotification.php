<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StaffNotification extends Model
{
    public const KINDS = ['QR-code', 'Manual'];

    // Level => [icon, colour class]
    public const LEVELS = [
        'success' => ['check_circle', 'green'],
        'warning' => ['warning', 'orange'],
        'danger' => ['report', 'red'],
        'info' => ['settings', 'blue'],
    ];

    protected $fillable = ['title', 'body', 'kind', 'level', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function scopeUnread(Builder $query): void
    {
        $query->whereNull('read_at');
    }

    public function getIconAttribute(): string
    {
        return self::LEVELS[$this->level][0] ?? 'notifications';
    }

    public function getColourAttribute(): string
    {
        return self::LEVELS[$this->level][1] ?? 'grey';
    }

    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }
}
