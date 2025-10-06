<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
            // purpose: signup, reset, stepup
            $table->string('purpose', 16);
            // key is phone or email identifier (normalized)
            $table->string('key');
            // store only a hash of the OTP (HMAC or bcrypt/argon of code)
            $table->string('code_hash');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('ua', 255)->nullable();
            $table->string('device_id', 100)->nullable();
            $table->timestamps();

            $table->index(['purpose', 'key']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
