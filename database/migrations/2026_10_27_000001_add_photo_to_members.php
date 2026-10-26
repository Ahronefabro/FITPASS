<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // The file name of the profile picture a member set in the app.
            if (! Schema::hasColumn('members', 'photo')) {
                $table->string('photo')->nullable()->after('qr_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'photo')) {
                $table->dropColumn('photo');
            }
        });
    }
};
