<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transfers', function (Blueprint $table) {
            // In MySQL, ensure we reference an existing column for positioning.
            // 'payout_id' does not exist in our schema; use 'payout_ref' instead.
            $table->string('refund_id')->nullable()->after('payout_ref');
            $table->string('refund_status', 20)->nullable()->after('refund_id');
            $table->timestamp('refund_attempted_at')->nullable()->after('refund_status');
            $table->timestamp('refund_completed_at')->nullable()->after('refund_attempted_at');
            $table->json('refund_response')->nullable()->after('refund_completed_at');
            $table->text('refund_error')->nullable()->after('refund_response');
        });
    }

    public function down()
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn([
                'refund_id',
                'refund_status',
                'refund_attempted_at',
                'refund_completed_at',
                'refund_response',
                'refund_error'
            ]);
        });
    }
};
