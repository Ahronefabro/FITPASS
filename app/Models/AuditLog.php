<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['user_name', 'action'];

    public static function record(string $action, ?string $user = null): void
    {
        static::create([
            'user_name' => $user ?? auth()->user()?->name ?? 'System',
            'action' => $action,
        ]);
    }
}
