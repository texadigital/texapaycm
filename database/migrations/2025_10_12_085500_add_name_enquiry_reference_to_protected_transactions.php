<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('protected_transactions', function (Blueprint $table) {
            $table->string('name_enquiry_reference')->nullable()->after('receiver_account_name');
        });
    }

    public function down(): void
    {
        Schema::table('protected_transactions', function (Blueprint $table) {
            $table->dropColumn('name_enquiry_reference');
        });
    }
};
