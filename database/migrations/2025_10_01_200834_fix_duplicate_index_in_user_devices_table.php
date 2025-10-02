<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration is no longer needed as we've fixed the duplicate index in the original migration
        // The table will be created with the correct indexes when running fresh migrations
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the index if rolling back
        Schema::table('user_devices', function (Blueprint $table) {
            $table->index(['device_token']);
        });
    }
};
