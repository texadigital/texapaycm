<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('auth_audits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('action', 32); // signup_request, signup_verify, reset_request, reset_verify, reset_complete, login, mfa_enroll, mfa_verify, lockout
            $table->string('result', 16); // success, fail, deny
            $table->string('reason', 255)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('ua', 255)->nullable();
            $table->string('device_id', 100)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'action']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_audits');
    }
};
