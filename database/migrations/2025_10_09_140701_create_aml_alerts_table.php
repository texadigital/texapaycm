<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aml_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transfer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rule_key');
            $table->string('severity')->default('medium'); // low|medium|high|critical
            $table->string('status')->default('open'); // open|investigating|closed
            $table->text('notes')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['status']);
            $table->index(['severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aml_alerts');
    }
};
