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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Amounts and FX
            $table->unsignedBigInteger('amount_xaf'); // store in minor units (e.g., cents) for precision
            $table->decimal('usd_to_xaf', 20, 10);
            $table->decimal('usd_to_ngn', 20, 10);
            $table->decimal('cross_rate_xaf_to_ngn', 20, 10); // derived (usd->ngn / usd->xaf)
            $table->decimal('adjusted_rate_xaf_to_ngn', 20, 10); // after margin/spread
            $table->unsignedBigInteger('fee_total_xaf')->default(0);
            $table->unsignedBigInteger('total_pay_xaf');
            $table->unsignedBigInteger('receive_ngn_minor'); // NGN minor units
            // Quote lifecycle
            $table->string('status')->default('active'); // active, expired, consumed, canceled
            $table->string('quote_ref')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('fx_fetched_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
