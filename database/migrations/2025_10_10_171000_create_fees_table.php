<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->string('corridor')->index(); // e.g., XAF_NGN
            $table->unsignedBigInteger('min_xaf')->default(0);
            $table->unsignedBigInteger('max_xaf')->default(0);
            $table->unsignedInteger('flat_xaf')->default(0);
            $table->unsignedInteger('percent_bps')->default(0);
            $table->unsignedInteger('cap_xaf')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
