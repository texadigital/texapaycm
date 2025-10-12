<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aml_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pack_id')->nullable()->constrained('aml_rule_packs')->nullOnDelete();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('severity')->default('medium');
            $table->boolean('is_active')->default(true);
            $table->json('expression')->nullable();
            $table->json('thresholds')->nullable();
            $table->timestamps();

            $table->index(['pack_id']);
            $table->index(['is_active']);
            $table->index(['severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aml_rules');
    }
};
