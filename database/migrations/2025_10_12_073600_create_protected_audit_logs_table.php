<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('protected_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('protected_transaction_id')->index();
            $table->string('actor_type', 20); // system|buyer|receiver|admin|provider
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('from_state', 30)->nullable();
            $table->string('to_state', 30)->nullable();
            $table->timestamp('at')->useCurrent();
            $table->string('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('protected_transaction_id')
                ->references('id')->on('protected_transactions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protected_audit_logs');
    }
};
