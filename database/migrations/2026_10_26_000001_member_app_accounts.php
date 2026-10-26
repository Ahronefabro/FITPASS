<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Members who sign up in the app log in with these. Members added at
            // the front desk have neither.
            if (! Schema::hasColumn('members', 'email')) {
                $table->string('email')->nullable()->unique()->after('contact');
            }

            if (! Schema::hasColumn('members', 'password')) {
                $table->string('password')->nullable()->after('email');
            }

            // An app sign-up has no dates until staff confirm the payment.
            $table->date('joined_at')->nullable()->change();
            $table->date('expires_at')->nullable()->change();
        });

        // Kept apart from the staff tokens so a member and a staff account
        // sharing an email never reset each other's password.
        Schema::create('member_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_password_reset_tokens');

        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'email')) {
                $table->dropUnique(['email']);
                $table->dropColumn('email');
            }

            if (Schema::hasColumn('members', 'password')) {
                $table->dropColumn('password');
            }
        });
    }
};
