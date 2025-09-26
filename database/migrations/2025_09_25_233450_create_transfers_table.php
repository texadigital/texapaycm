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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();

            // Recipient details (from Safe Haven Name Enquiry)
            $table->string('recipient_bank_code', 32);
            $table->string('recipient_bank_name');
            $table->string('recipient_account_number', 32);
            $table->string('recipient_account_name');

            // Amounts (store money in minor units)
            $table->unsignedBigInteger('amount_xaf');
            $table->unsignedBigInteger('fee_total_xaf')->default(0);
            $table->unsignedBigInteger('total_pay_xaf');
            $table->unsignedBigInteger('receive_ngn_minor');

            // FX snapshot for audit
            $table->decimal('adjusted_rate_xaf_to_ngn', 20, 10);
            $table->decimal('usd_to_xaf', 20, 10);
            $table->decimal('usd_to_ngn', 20, 10);
            $table->timestamp('fx_fetched_at')->nullable();

            // Provider references & statuses
            $table->string('payin_provider')->default('pawapay');
            $table->string('payin_ref')->nullable()->unique();
            $table->string('payin_status')->nullable(); // pending, success, failed, canceled
            $table->timestamp('payin_at')->nullable();

            $table->string('payout_provider')->default('safehaven');
            $table->string('payout_ref')->nullable()->unique();
            $table->string('payout_status')->nullable(); // pending, success, failed
            $table->timestamp('payout_initiated_at')->nullable();
            $table->timestamp('payout_completed_at')->nullable();

            // Overall state & timeline
            $table->string('status')->default('quote_created'); // quote_created, payin_pending, payin_success, payout_pending, payout_success, failed
            $table->json('timeline')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['payin_status']);
            $table->index(['payout_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
