<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aml_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('payload')->nullable();
            $table->string('checksum')->nullable();
            $table->timestamps();

            $table->index(['action']);
            $table->index(['subject_type','subject_id']);
            $table->index(['actor_type','actor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aml_audit_logs');
    }
};
