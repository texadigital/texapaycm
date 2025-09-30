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
        Schema::create('daily_transaction_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('transaction_date');
            $table->integer('total_amount_xaf')->default(0);
            $table->integer('transaction_count')->default(0);
            $table->integer('successful_amount_xaf')->default(0); // Only successful transactions
            $table->integer('successful_count')->default(0); // Only successful transactions
            $table->json('transaction_details')->nullable(); // Store additional details if needed
            $table->timestamps();
            
            // Ensure one summary per user per date
            $table->unique(['user_id', 'transaction_date']);
            
            // Indexes for performance
            $table->index(['user_id', 'transaction_date']);
            $table->index('transaction_date'); // For admin reports
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_transaction_summaries');
    }
};
