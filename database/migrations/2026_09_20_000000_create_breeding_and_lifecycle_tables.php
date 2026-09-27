<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mate_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('to_character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->unique(['from_character_id', 'to_character_id']);
        });

        Schema::create('pregnancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('female_character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('male_character_id')->constrained('characters')->cascadeOnDelete();
            $table->dateTime('conceived_at');
            $table->dateTime('due_at');
            $table->string('status')->default('pregnant');
            $table->timestamps();
            $table->index(['status', 'due_at']);
        });

        Schema::create('litters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregnancy_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('kit_count');
            $table->unsignedTinyInteger('surviving_count')->default(0);
            $table->dateTime('born_at');
            $table->timestamps();
        });

        Schema::create('litter_kits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('litter_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('sex');
            $table->string('coat_notes')->nullable();
            $table->boolean('has_disability')->default(false);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('litter_kits');
        Schema::dropIfExists('litters');
        Schema::dropIfExists('pregnancies');
        Schema::dropIfExists('mate_requests');
    }
};
