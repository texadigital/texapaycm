<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('policy_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('terms_version', 32)->nullable();
            $table->string('privacy_version', 32)->nullable();
            $table->timestamp('accepted_at');
            $table->string('signature')->nullable();
            $table->string('signature_hash', 190)->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('policy_acceptances');
    }
};
