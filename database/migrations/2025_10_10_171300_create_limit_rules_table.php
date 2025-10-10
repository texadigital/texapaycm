<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('limit_rules', function (Blueprint $table) {
            $table->id();
            $table->string('scope'); // user, role, instrument, corridor
            $table->string('key')->nullable(); // e.g., user_id or role name
            $table->string('metric'); // daily_amount, monthly_amount, per_tx_amount, daily_count, monthly_count
            $table->unsignedBigInteger('threshold')->default(0);
            $table->string('window'); // daily, monthly, per_tx
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
