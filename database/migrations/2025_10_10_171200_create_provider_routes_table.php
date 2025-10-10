<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('provider_routes', function (Blueprint $table) {
            $table->id();
            $table->string('corridor')->index();
            $table->string('provider_code')->index();
            $table->unsignedInteger('weight')->default(100);
            $table->json('msisdn_prefixes')->nullable();
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_routes');
    }
};
