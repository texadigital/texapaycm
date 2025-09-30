<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('name');
            $table->string('notification_email')->nullable()->after('email');
            $table->string('avatar_path', 500)->nullable()->after('notification_email');
            $table->timestamp('phone_verified_at')->nullable()->after('avatar_path');
            $table->timestamp('email_verified_at')->nullable()->change(); // ensure exists & nullable
            $table->timestamp('profile_completed_at')->nullable()->after('phone_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'full_name',
                'notification_email',
                'avatar_path',
                'phone_verified_at',
                'profile_completed_at',
            ]);
        });
    }
};
