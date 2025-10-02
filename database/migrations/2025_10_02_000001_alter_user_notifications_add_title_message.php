<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_notifications')) {
            Schema::table('user_notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('user_notifications', 'title')) {
                    $table->string('title', 255)->after('type');
                }
                if (!Schema::hasColumn('user_notifications', 'message')) {
                    $table->text('message')->after('title');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_notifications')) {
            Schema::table('user_notifications', function (Blueprint $table) {
                if (Schema::hasColumn('user_notifications', 'message')) {
                    $table->dropColumn('message');
                }
                if (Schema::hasColumn('user_notifications', 'title')) {
                    $table->dropColumn('title');
                }
            });
        }
    }
};
