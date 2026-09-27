<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('shop_item_id')->constrained('shop_items')->cascadeOnDelete();
            $table->foreignId('applied_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('applied_at');
            $table->timestamps();
            $table->unique(['character_id', 'shop_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_items');
    }
};
