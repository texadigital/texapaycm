<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notification_events')) {
            return;
        }

        // Ensure event_key exists
        if (!Schema::hasColumn('notification_events', 'event_key')) {
            DB::statement("ALTER TABLE `notification_events` ADD COLUMN `event_key` VARCHAR(255) NULL AFTER `event_type`");
        }

        // Backfill event_key from dedupe_key if present and event_key is NULL
        if (Schema::hasColumn('notification_events', 'dedupe_key')) {
            DB::statement("UPDATE `notification_events` SET `event_key` = `dedupe_key` WHERE `event_key` IS NULL");
        }
        // For any still-null event_key, fill with synthetic value to satisfy NOT NULL
        DB::statement("UPDATE `notification_events` SET `event_key` = CONCAT('ek_', `id`) WHERE `event_key` IS NULL");

        // Drop old unique index on (user_id, event_type, dedupe_key) if it exists
        try { DB::statement("ALTER TABLE `notification_events` DROP INDEX `notification_events_user_id_event_type_dedupe_key_unique`"); } catch (\Throwable $e) {}
        try { DB::statement("ALTER TABLE `notification_events` DROP INDEX `user_id_event_type_dedupe_key`"); } catch (\Throwable $e) {}

        // Add new unique index on (user_id, event_type, event_key) if not present
        try { DB::statement("ALTER TABLE `notification_events` ADD UNIQUE `notification_events_user_type_event_key_unique` (`user_id`,`event_type`,`event_key`)"); } catch (\Throwable $e) {}

        // Finally, drop dedupe_key column if present (to remove NOT NULL constraint/source of errors)
        if (Schema::hasColumn('notification_events', 'dedupe_key')) {
            try { DB::statement("ALTER TABLE `notification_events` DROP COLUMN `dedupe_key`"); } catch (\Throwable $e) {}
        }

        // Make event_key NOT NULL
        try { DB::statement("ALTER TABLE `notification_events` MODIFY `event_key` VARCHAR(255) NOT NULL"); } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        if (!Schema::hasTable('notification_events')) {
            return;
        }
        // Re-add dedupe_key column (nullable) if needed
        if (!Schema::hasColumn('notification_events', 'dedupe_key')) {
            try { DB::statement("ALTER TABLE `notification_events` ADD COLUMN `dedupe_key` VARCHAR(255) NULL"); } catch (\Throwable $e) {}
        }
        // Optionally drop the new unique and recreate the old one
        try { DB::statement("ALTER TABLE `notification_events` DROP INDEX `notification_events_user_type_event_key_unique`"); } catch (\Throwable $e) {}
        try { DB::statement("ALTER TABLE `notification_events` ADD UNIQUE `notification_events_user_id_event_type_dedupe_key_unique` (`user_id`,`event_type`,`dedupe_key`)"); } catch (\Throwable $e) {}
    }
};
