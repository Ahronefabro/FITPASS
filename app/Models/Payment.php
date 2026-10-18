<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public const KINDS = ['Monthly', 'Walk-in'];

    public const METHODS = ['Gcash', 'Cash'];

    protected $fillable = ['member_id', 'name', 'paid_on', 'kind', 'amount', 'method'];

    protected $casts = ['paid_on' => 'date', 'amount' => 'decimal:2'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
