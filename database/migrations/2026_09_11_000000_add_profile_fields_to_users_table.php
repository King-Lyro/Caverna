<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('name');
            $table->string('avatar_path')->nullable()->after('bio');
            $table->string('discord_username')->nullable()->after('avatar_path');
            $table->string('facebook_url')->nullable()->after('discord_username');
            $table->string('instagram_url')->nullable()->after('facebook_url');
            $table->string('pronouns')->nullable()->after('instagram_url');
            $table->unsignedTinyInteger('age')->nullable()->after('pronouns');
            $table->string('location')->nullable()->after('age');
            $table->string('timezone')->nullable()->after('location');
            $table->boolean('hide_personal_info')->default(false)->after('timezone');
            $table->boolean('hide_current_page')->default(false)->after('hide_personal_info');
            $table->boolean('is_absent')->default(false)->after('hide_current_page');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bio', 'avatar_path', 'discord_username', 'facebook_url', 'instagram_url', 'pronouns', 'age', 'location', 'timezone', 'hide_personal_info', 'hide_current_page', 'is_absent']);
        });
    }
};
