<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Off Duty is gone. It never stopped anyone signing in, so those accounts
 * become Active and keep exactly the access they had.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('staff')->where('status', 'Off Duty')->update(['status' => 'Active']);
    }

    public function down(): void
    {
        // Which accounts were Off Duty is not kept, so there is nothing to restore.
    }
};
