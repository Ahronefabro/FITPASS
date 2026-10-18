<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // The staff screens show a contact number and scan a printed code.
            if (! Schema::hasColumn('members', 'contact')) {
                $table->string('contact')->nullable();
            }

            if (! Schema::hasColumn('members', 'qr_code')) {
                $table->string('qr_code')->nullable()->unique();
            }
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Walk-ins are not always members, so the name is kept either way.
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->date('paid_on')->index();
            $table->string('kind');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('method');
            $table->timestamps();
        });

        Schema::create('staff_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('body');
            $table->string('kind');
            $table->string('level');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_notifications');
        Schema::dropIfExists('payments');

        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'qr_code')) {
                $table->dropUnique(['qr_code']);
                $table->dropColumn('qr_code');
            }

            if (Schema::hasColumn('members', 'contact')) {
                $table->dropColumn('contact');
            }
        });
    }
};
