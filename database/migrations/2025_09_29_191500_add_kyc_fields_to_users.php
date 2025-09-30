<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('kyc_level')->default(0)->after('sms_notifications');
            $table->string('kyc_status')->default('unverified')->after('kyc_level');
            $table->string('kyc_provider_ref')->nullable()->after('kyc_status');
            $table->timestamp('kyc_verified_at')->nullable()->after('kyc_provider_ref');
            $table->json('kyc_meta')->nullable()->after('kyc_verified_at');
            $table->unsignedSmallInteger('kyc_attempts')->default(0)->after('kyc_meta');
            $table->index(['kyc_level']);
            $table->index(['kyc_status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['kyc_level']);
            $table->dropIndex(['kyc_status']);
            $table->dropColumn(['kyc_level','kyc_status','kyc_provider_ref','kyc_verified_at','kyc_meta','kyc_attempts']);
        });
    }
};
