<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_name', 80)->nullable();
        });

        Schema::table('forum_posts', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_name', 80)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropColumn('guest_name');
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('forum_threads', function (Blueprint $table) {
            $table->dropColumn('guest_name');
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
