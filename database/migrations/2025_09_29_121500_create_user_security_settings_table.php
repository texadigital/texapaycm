<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('user_security_settings')) {
            // Table already exists (dev runs, sqlite). Consider this migration successful.
            return;
        }
        Schema::create('user_security_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->json('two_factor_recovery_codes')->nullable();
            $table->boolean('pin_enabled')->default(false);
            $table->string('pin_hash')->nullable();
            $table->boolean('sms_login_enabled')->default(false);
            $table->boolean('face_id_enabled')->default(false);
            $table->timestamp('last_security_update')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_security_settings');
    }
};
