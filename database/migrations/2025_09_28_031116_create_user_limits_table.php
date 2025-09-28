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
        Schema::create('user_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('daily_limit_xaf')->default(500000); // 500,000 XAF default
            $table->integer('monthly_limit_xaf')->default(5000000); // 5,000,000 XAF default
            $table->integer('daily_count_limit')->default(10); // 10 transactions per day
            $table->integer('monthly_count_limit')->default(100); // 100 transactions per month
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable(); // Admin notes
            $table->timestamp('last_updated_by_admin')->nullable();
            $table->timestamps();
            
            // Ensure one limit record per user
            $table->unique('user_id');
            
            // Indexes for performance
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_limits');
    }
};
