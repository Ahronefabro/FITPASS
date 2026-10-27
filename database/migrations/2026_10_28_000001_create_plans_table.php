<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Plans and their prices used to be written into the code. They now live here
 * so the owner can change a price or add a plan without a new release.
 * Members keep the plan's name, as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('price', 10, 2);
            // The plan's colour on the owner's charts.
            $table->string('colour', 7);
            $table->timestamps();
        });

        // The plans and prices the code had, so nothing changes on the day.
        DB::table('plans')->insert([
            ['name' => 'Regular', 'price' => 1000, 'colour' => '#00FF66', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Student', 'price' => 800, 'colour' => '#40C4FF', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Senior', 'price' => 700, 'colour' => '#2196F3', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
