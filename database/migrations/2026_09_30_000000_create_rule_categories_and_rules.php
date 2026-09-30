<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $community = DB::table('rule_categories')->insertGetId(['name' => 'Community', 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now]);
        $storytelling = DB::table('rule_categories')->insertGetId(['name' => 'Storytelling', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('rules')->insert([
            ['rule_category_id' => $community, 'title' => 'Write with care', 'description' => 'Keep IC posts at least 70 words, respect the people behind the characters, and leave room for other writers to contribute.', 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['rule_category_id' => $storytelling, 'title' => 'Let stories breathe', 'description' => 'Characters can change, fail, and surprise one another. Staff are here to protect the shared world, not to dictate every story.', 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('rules');
        Schema::dropIfExists('rule_categories');
    }
};