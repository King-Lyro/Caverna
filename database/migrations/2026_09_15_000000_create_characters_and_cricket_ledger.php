<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sex');
            $table->decimal('age_moons', 5, 1)->default(6);
            $table->string('allegiance');
            $table->string('role')->default('warrior');
            $table->unsignedTinyInteger('energy')->default(100);
            $table->string('status')->default('active');
            $table->json('images')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('mate')->nullable();
            $table->text('kits')->nullable();
            $table->string('looks', 255);
            $table->text('appearance');
            $table->text('personality');
            $table->text('history');
            $table->boolean('adopted')->default(false);
            $table->timestamp('last_ic_post_at')->nullable();
            $table->timestamp('inactive_at')->nullable();
            $table->timestamp('died_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('cricket_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->string('type');
            $table->string('description');
            $table->nullableMorphs('reference');
            $table->timestamps();
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cricket_ledger');
        Schema::dropIfExists('characters');
    }
};
