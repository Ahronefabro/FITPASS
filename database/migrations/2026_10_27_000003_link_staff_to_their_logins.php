<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each row in the staff directory now belongs to exactly one login. They used
 * to be matched by name, so two rows with the same name both governed the one
 * login, and a row added from the owner's screen had no login at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            // Null only for rows left over from before, which have no login.
            $table->foreignId('user_id')->nullable()->unique()->after('id')
                ->constrained()->cascadeOnDelete();
        });

        // The directory said 'Admins' where the sign-in expects 'Admin'.
        DB::table('staff')->where('role', 'Admins')->update(['role' => 'Admin']);

        // Link the rows that match a login by name, one last time. Where a name
        // has several rows the oldest takes the login and the rest are left
        // without one, which the staff screen shows as "No login". The owner is
        // left out so the directory can never change the owner's account.
        $logins = DB::table('users')
            ->whereIn('role', ['Admin', 'Staff', 'Front Desk', 'Trainers'])
            ->get(['id', 'name']);

        foreach ($logins as $login) {
            $row = DB::table('staff')->whereNull('user_id')
                ->where('name', $login->name)->orderBy('id')->first();

            if ($row) {
                DB::table('staff')->where('id', $row->id)->update(['user_id' => $login->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        DB::table('staff')->where('role', 'Admin')->update(['role' => 'Admins']);
    }
};
