<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->date('dob')->nullable()->after('last_name');
            $table->string('username')->nullable()->unique()->after('dob');
            $table->string('email')->nullable()->unique()->change();
            // Address fields
            $table->string('address_line1')->nullable()->after('username');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('city')->nullable()->after('address_line2');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state');
            $table->string('country', 2)->nullable()->after('postal_code');
            // T&Cs
            $table->string('terms_version_signed')->nullable()->after('country');
            $table->timestamp('terms_signed_at')->nullable()->after('terms_version_signed');
            $table->boolean('marketing_opt_in')->default(false)->after('terms_signed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name','last_name','dob','username','address_line1','address_line2','city','state','postal_code','country','terms_version_signed','terms_signed_at','marketing_opt_in'
            ]);
            // Note: we do not revert email unique nullable here automatically.
        });
    }
};
