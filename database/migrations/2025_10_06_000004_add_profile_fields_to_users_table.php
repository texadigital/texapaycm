<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users', 'dob')) {
                $table->date('dob')->nullable()->after('last_name');
            }
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->after('dob');
            }
            // Address fields
            if (!Schema::hasColumn('users', 'address_line1')) {
                $table->string('address_line1')->nullable()->after('username');
            }
            if (!Schema::hasColumn('users', 'address_line2')) {
                $table->string('address_line2')->nullable()->after('address_line1');
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('address_line2');
            }
            if (!Schema::hasColumn('users', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (!Schema::hasColumn('users', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('state');
            }
            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country', 2)->nullable()->after('postal_code');
            }
            // T&Cs
            if (!Schema::hasColumn('users', 'terms_version_signed')) {
                $table->string('terms_version_signed')->nullable()->after('country');
            }
            if (!Schema::hasColumn('users', 'terms_signed_at')) {
                $table->timestamp('terms_signed_at')->nullable()->after('terms_version_signed');
            }
            if (!Schema::hasColumn('users', 'marketing_opt_in')) {
                $table->boolean('marketing_opt_in')->default(false)->after('terms_signed_at');
            }
        });

        // Ensure username unique index exists if column exists
        if (Schema::hasColumn('users', 'username')) {
            $hasUsernameIdx = collect(\DB::select("SHOW INDEX FROM `users` WHERE Key_name = 'users_username_unique'"))->isNotEmpty();
            if (!$hasUsernameIdx) {
                Schema::table('users', function (Blueprint $table) { $table->unique('username'); });
            }
        }

        // Handle email column idempotently: make nullable; ensure unique index exists but do not duplicate
        if (Schema::hasColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email')->nullable()->change();
            });
            $hasIndex = collect(\DB::select("SHOW INDEX FROM `users` WHERE Key_name = 'users_email_unique'"))->isNotEmpty();
            if (!$hasIndex) {
                Schema::table('users', function (Blueprint $table) {
                    $table->unique('email');
                });
            }
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email')->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['first_name','last_name','dob','username','address_line1','address_line2','city','state','postal_code','country','terms_version_signed','terms_signed_at','marketing_opt_in'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
            // Note: we do not revert email unique/nullable here.
        });
    }
};
