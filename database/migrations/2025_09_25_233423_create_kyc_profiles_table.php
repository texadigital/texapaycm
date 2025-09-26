<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kyc_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('phone', 32);
            $table->string('id_type')->nullable(); // e.g., National ID, Passport
            $table->string('id_number')->nullable();
            $table->string('id_image_path')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('status')->default('verified'); // minimal KYC flow
            $table->timestamps();

            $table->unique(['user_id']);
            $table->index(['phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_profiles');
    }
};
