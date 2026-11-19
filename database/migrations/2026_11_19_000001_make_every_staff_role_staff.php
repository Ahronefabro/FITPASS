<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The staff directory now has one role, Staff. Front Desk, Trainers and Admin
 * accounts become Staff, in the directory and on the login, so they still
 * sign in to the staff portal. Admins lose the owner portal with it.
 */
return new class extends Migration
{
    private const RETIRED = ['Front Desk', 'Trainers', 'Admin'];

    public function up(): void
    {
        DB::table('staff')->whereIn('role', self::RETIRED)->update(['role' => 'Staff']);
        DB::table('users')->whereIn('role', self::RETIRED)->update(['role' => 'Staff']);
    }

    /** Which role each account had is not kept, so there is nothing to restore. */
    public function down(): void {}
};
