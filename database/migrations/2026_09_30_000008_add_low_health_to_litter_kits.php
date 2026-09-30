<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('litter_kits', function (Blueprint $table) {
            $table->boolean('low_health')->default(false);
        });
        if (Schema::hasColumn('litter_kits', 'energy')) {
            DB::table('litter_kits')->where('status', 'surviving')->where('energy', 25)->update(['low_health' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('litter_kits', function (Blueprint $table) {
            $table->dropColumn('low_health');
        });
    }
};