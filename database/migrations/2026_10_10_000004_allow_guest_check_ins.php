<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            // Walk-ins and first-timers are not on file, so their name is typed in
            // by hand and no member is attached to the visit.
            if (! Schema::hasColumn('check_ins', 'guest_name')) {
                $table->string('guest_name')->nullable()->after('member_id');
            }

            $table->unsignedBigInteger('member_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            if (Schema::hasColumn('check_ins', 'guest_name')) {
                $table->dropColumn('guest_name');
            }
        });
    }
};
