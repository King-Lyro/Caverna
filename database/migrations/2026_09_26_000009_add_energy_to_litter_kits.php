<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('litter_kits', function (Blueprint $table) {
            $table->unsignedTinyInteger('energy')->default(0)->after('sex');
        });
    }

    public function down(): void
    {
        Schema::table('litter_kits', function (Blueprint $table) {
            $table->dropColumn('energy');
        });
    }
};
