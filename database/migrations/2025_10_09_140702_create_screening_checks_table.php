<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('screening_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('kyc_update');
            $table->string('provider')->nullable();
            $table->string('status')->default('running');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['status']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screening_checks');
    }
};
