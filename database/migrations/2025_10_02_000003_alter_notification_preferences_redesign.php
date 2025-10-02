<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_preferences')) {
            Schema::table('notification_preferences', function (Blueprint $table) {
                // Redesign to per-type booleans stored in one row per type
                if (Schema::hasColumn('notification_preferences', 'channel')) {
                    $table->dropUnique(['user_id','type','channel']);
                }
            });

            Schema::table('notification_preferences', function (Blueprint $table) {
                if (!Schema::hasColumn('notification_preferences', 'notification_type')) {
                    $table->string('notification_type', 50)->after('user_id');
                }
                if (!Schema::hasColumn('notification_preferences', 'email_enabled')) {
                    $table->boolean('email_enabled')->default(true)->after('notification_type');
                }
                if (!Schema::hasColumn('notification_preferences', 'sms_enabled')) {
                    $table->boolean('sms_enabled')->default(false)->after('email_enabled');
                }
                if (!Schema::hasColumn('notification_preferences', 'push_enabled')) {
                    $table->boolean('push_enabled')->default(false)->after('sms_enabled');
                }
                if (!Schema::hasColumn('notification_preferences', 'in_app_enabled')) {
                    $table->boolean('in_app_enabled')->default(true)->after('push_enabled');
                }

                // Create unique index on (user_id, notification_type)
                $table->unique(['user_id','notification_type'], 'notification_preferences_user_type_unique');
            });

            // Drop old columns we no longer use
            Schema::table('notification_preferences', function (Blueprint $table) {
                foreach (['type','channel','enabled'] as $old) {
                    if (Schema::hasColumn('notification_preferences', $old)) {
                        $table->dropColumn($old);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_preferences')) {
            Schema::table('notification_preferences', function (Blueprint $table) {
                // Recreate old columns
                if (!Schema::hasColumn('notification_preferences', 'type')) {
                    $table->string('type', 50)->nullable();
                }
                if (!Schema::hasColumn('notification_preferences', 'channel')) {
                    $table->string('channel', 20)->nullable();
                }
                if (!Schema::hasColumn('notification_preferences', 'enabled')) {
                    $table->boolean('enabled')->default(true);
                }
                try { $table->dropUnique('notification_preferences_user_type_unique'); } catch (\Throwable $e) {}
                // Drop new columns
                foreach (['notification_type','email_enabled','sms_enabled','push_enabled','in_app_enabled'] as $col) {
                    if (Schema::hasColumn('notification_preferences', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
