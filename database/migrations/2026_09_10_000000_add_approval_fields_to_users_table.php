<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->after('email');
            $table->string('status')->default('pending')->after('role');
            $table->text('roleplay_sample')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('roleplay_sample');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status', 'roleplay_sample', 'approved_at']);
        });
    }
};
