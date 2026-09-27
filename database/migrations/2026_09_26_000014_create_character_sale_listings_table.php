<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_sale_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('price');
            $table->string('status')->default('available');
            $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('sold_at')->nullable();
            $table->timestamps();
            $table->unique(['character_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_sale_listings');
    }
};
