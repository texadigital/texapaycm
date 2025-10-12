<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('limit_rules')) {
            // Table already exists from a partial run; skip creation to avoid duplicate error.
            return;
        }
        Schema::create('limit_rules', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 64); // user, role, instrument, corridor
            $table->string('key', 128)->nullable(); // e.g., user_id or role name
            $table->string('metric', 64); // daily_amount, monthly_amount, per_tx_amount, daily_count, monthly_count
            $table->unsignedBigInteger('threshold')->default(0);
            $table->string('window', 32); // daily, monthly, per_tx
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['scope','key','metric','window']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('limit_rules');
    }
};
