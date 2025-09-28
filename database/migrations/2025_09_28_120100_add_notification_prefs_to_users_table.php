<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email_notifications')) {
                $table->boolean('email_notifications')->default(true)->after('profile_completed_at');
            }
            if (!Schema::hasColumn('users', 'sms_notifications')) {
                $table->boolean('sms_notifications')->default(false)->after('email_notifications');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email_notifications')) {
                $table->dropColumn('email_notifications');
            }
            if (Schema::hasColumn('users', 'sms_notifications')) {
                $table->dropColumn('sms_notifications');
            }
        });
    }
};
