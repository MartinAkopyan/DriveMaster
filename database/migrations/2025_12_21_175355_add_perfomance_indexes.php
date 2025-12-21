<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->index(['status', 'start_time'], 'lessons_status_start_time_index');
            $table->index('created_at', 'lessons_created_at_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'is_approved'], 'users_role_approved_index');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->index('phone', 'profiles_phone_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_id', 'read_at'], 'notifications_user_read_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex('lessons_status_start_time_index');
            $table->dropIndex('lessons_created_at_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_approved_index');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex('profiles_phone_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_read_index');
        });
    }
};
