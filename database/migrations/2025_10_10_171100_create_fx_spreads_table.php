<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fx_spreads', function (Blueprint $table) {
            $table->id();
            $table->string('corridor')->index(); // e.g., XAF_NGN
            $table->string('provider')->nullable();
            $table->integer('margin_bps')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fx_spreads');
    }
};
