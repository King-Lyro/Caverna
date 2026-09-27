<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('litter_kits', function (Blueprint $table) {
            $table->foreignId('character_id')->nullable()->after('litter_id')->constrained('characters')->nullOnDelete();
            $table->index('character_id');
        });
    }

    public function down(): void
    {
        Schema::table('litter_kits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('character_id');
        });
    }
};
