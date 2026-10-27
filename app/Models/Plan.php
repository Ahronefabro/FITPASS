<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A membership plan and what it costs. Members store the plan's name, so a
 * plan's name is fixed once it exists; its price and colour can change.
 */
class Plan extends Model
{
    protected $fillable = ['name', 'price', 'colour'];

    protected $casts = ['price' => 'decimal:2'];

    /** @return list<string> */
    public static function names(): array
    {
        return static::orderBy('id')->pluck('name')->all();
    }

    /** @return array<string, string> plan name => chart colour */
    public static function colours(): array
    {
        return static::orderBy('id')->pluck('colour', 'name')->all();
    }

    /** @return array<string, float> plan name => price */
    public static function prices(): array
    {
        return static::orderBy('id')->pluck('price', 'name')->map(fn ($price) => (float) $price)->all();
    }

    public static function priceOf(string $name): float
    {
        return (float) static::where('name', $name)->value('price');
    }

    public function members()
    {
        return $this->hasMany(Member::class, 'plan', 'name');
    }
}
