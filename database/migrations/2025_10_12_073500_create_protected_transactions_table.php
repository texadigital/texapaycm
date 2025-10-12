<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('protected_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('buyer_user_id')->index();

            $table->string('receiver_bank_code', 32);
            $table->string('receiver_bank_name')->nullable();
            $table->string('receiver_account_number', 32);
            $table->string('receiver_account_name')->nullable();

            $table->unsignedBigInteger('amount_ngn_minor');
            $table->unsignedBigInteger('fee_ngn_minor')->default(0);
            $table->string('fee_rule_version', 20)->nullable();
            $table->json('fee_components')->nullable();

            $table->string('funding_source', 32)->nullable(); // card | virtual_account
            $table->string('funding_provider', 50)->nullable();
            $table->string('funding_ref', 100)->unique();
            $table->string('funding_status', 20)->default('pending');

            $table->string('escrow_state', 30)->index(); // created|locked|awaiting_approval|released|disputed|expired|refunded|partial_refund
            $table->timestamp('auto_release_at')->nullable()->index();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('disputed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->string('payout_ref', 100)->nullable()->unique();
            $table->string('payout_status', 20)->nullable();
            $table->timestamp('payout_attempted_at')->nullable();
            $table->timestamp('payout_completed_at')->nullable();

            $table->string('va_account_number', 32)->nullable();
            $table->string('va_bank_code', 32)->nullable();
            $table->string('va_reference', 100)->nullable();

            $table->string('card_intent_id', 100)->nullable();
            $table->string('card_provider_ref', 100)->nullable();

            $table->json('webhook_event_ids')->nullable();
            $table->json('audit_timeline')->nullable();

            $table->timestamps();

            $table->index(['buyer_user_id', 'escrow_state']);
            $table->index(['receiver_account_number']);

            $table->foreign('buyer_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protected_transactions');
    }
};
