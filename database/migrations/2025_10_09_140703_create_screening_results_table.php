<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('screening_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screening_check_id')->constrained('screening_checks')->cascadeOnDelete();
            $table->string('match_type');
            $table->string('name')->nullable();
            $table->string('list_source')->nullable();
            $table->unsignedSmallInteger('score')->default(0);
            $table->string('decision')->default('pass');
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['match_type']);
            $table->index(['decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screening_results');
    }
};
