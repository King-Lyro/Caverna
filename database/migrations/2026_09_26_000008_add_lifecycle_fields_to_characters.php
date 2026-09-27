<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->string('eye_color')->nullable()->after('sex');
            $table->boolean('role_locked')->default(false)->after('role');
            $table->boolean('is_frozen')->default(false)->after('status');
            $table->string('frozen_reason')->nullable()->after('is_frozen');
            $table->dateTime('archived_at')->nullable()->after('died_at');
            $table->string('forum_avatar_path')->nullable()->after('avatar_path');
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn(['eye_color', 'role_locked', 'is_frozen', 'frozen_reason', 'archived_at', 'forum_avatar_path']);
        });
    }
};
