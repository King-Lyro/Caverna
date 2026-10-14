<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeding_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('female_character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('male_character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'female_character_id']);
            $table->index(['status', 'male_character_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeding_requests');
    }
};
