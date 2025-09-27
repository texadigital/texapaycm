<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->string('payout_idempotency_key')->nullable()->unique()->after('payout_ref');
            $table->timestamp('payout_attempted_at')->nullable()->after('payout_ref');
            $table->string('last_payout_error')->nullable()->after('payout_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn([
                'payout_idempotency_key',
                'payout_attempted_at',
                'last_payout_error'
            ]);
        });
    }
};
