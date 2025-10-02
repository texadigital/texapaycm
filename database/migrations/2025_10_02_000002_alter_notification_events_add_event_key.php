<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notification_events')) {
            return;
        }

        // Add event_key column if missing
        Schema::table('notification_events', function (Blueprint $table) {
            if (!Schema::hasColumn('notification_events', 'event_key')) {
                $table->string('event_key', 255)->nullable()->after('event_type');
            }
        });

        // Backfill event_key from dedupe_key when present
        if (Schema::hasColumn('notification_events', 'event_key')) {
            if (Schema::hasColumn('notification_events', 'dedupe_key')) {
                DB::table('notification_events')->whereNull('event_key')->update([
                    'event_key' => DB::raw('dedupe_key')
                ]);
            }
            // Ensure non-null for unique index
            DB::table('notification_events')->whereNull('event_key')->update([
                'event_key' => DB::raw("CONCAT('ek_', id)")
            ]);
        }

        // Add new unique index on (user_id, event_type, event_key)
        Schema::table('notification_events', function (Blueprint $table) {
            if (Schema::hasColumn('notification_events', 'event_key')) {
                try {
                    $table->unique(['user_id','event_type','event_key'], 'notification_events_user_type_event_key_unique');
                } catch (\Throwable $e) {
                    // ignore if already exists
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('notification_events')) {
            return;
        }
        Schema::table('notification_events', function (Blueprint $table) {
            try { $table->dropUnique('notification_events_user_type_event_key_unique'); } catch (\Throwable $e) {}
            if (Schema::hasColumn('notification_events', 'event_key')) {
                $table->dropColumn('event_key');
            }
        });
    }
};
