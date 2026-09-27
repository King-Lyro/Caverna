<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sidebar_modules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('link_text')->nullable();
            $table->string('link_url')->nullable();
            $table->string('placement')->default('right');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->index(['placement', 'is_enabled', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sidebar_modules');
    }
};
