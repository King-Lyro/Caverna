<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->boolean('male_calico')->default(false)->after('disability');
            $table->boolean('chimera_mosaicism')->default(false)->after('male_calico');
            $table->boolean('karpati_roan_salmiak')->default(false)->after('chimera_mosaicism');
            $table->boolean('white_sepia')->default(false)->after('karpati_roan_salmiak');
            $table->boolean('albino')->default(false)->after('white_sepia');
            $table->boolean('purebred')->default(false)->after('albino');
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn([
                'male_calico',
                'chimera_mosaicism',
                'karpati_roan_salmiak',
                'white_sepia',
                'albino',
                'purebred',
            ]);
        });
    }
};
