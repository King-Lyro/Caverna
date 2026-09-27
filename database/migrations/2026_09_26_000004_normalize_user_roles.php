<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'member')->update(['role' => 'registered']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('registered')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'registered')->update(['role' => 'member']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->change();
        });
    }
};
