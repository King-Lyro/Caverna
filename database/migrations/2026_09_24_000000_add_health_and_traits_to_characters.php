<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->string('health_status')->default('healthy')->after('status');
            $table->json('ailments')->nullable()->after('health_status');
            $table->json('traits')->nullable()->after('ailments');
            $table->timestamp('care_updated_at')->nullable()->after('traits');
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn(['health_status', 'ailments', 'traits', 'care_updated_at']);
        });
    }
};
