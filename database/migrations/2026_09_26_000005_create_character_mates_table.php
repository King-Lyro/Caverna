<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_mates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('mate_character_id')->constrained('characters')->cascadeOnDelete();
            $table->dateTime('accepted_at')->nullable();
            $table->timestamps();
            $table->unique(['character_id', 'mate_character_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_mates');
    }
};
