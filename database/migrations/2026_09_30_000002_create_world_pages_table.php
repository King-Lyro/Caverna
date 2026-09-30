<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('world_pages', function (Blueprint $table) {
            $table->id();
            $table->string('kind');
            $table->string('slug');
            $table->string('name');
            $table->text('summary');
            $table->string('eyebrow');
            $table->string('title');
            $table->text('intro');
            $table->longText('body');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['kind', 'slug']);
            $table->index(['kind', 'sort_order']);
        });

        $pages = [
            ['clans', 'thunderclan', 'ThunderClan', 'Steady hearts, open clearings, and a long memory for those who stand beside them.', 'Woodland paths and open clearings are the heart of ThunderClan territory. Every character brings a different reason to call it home.'],
            ['clans', 'riverclan', 'RiverClan', 'A life shaped by water, patience, and the glittering edges of change.', 'RiverClan lives alongside the water. Its shifting banks make room for stories about belonging, patience, and change.'],
            ['clans', 'shadowclan', 'ShadowClan', 'The pines hold their secrets close. ShadowClan values resilience and quiet observation.', 'Beneath the pines, ShadowClan cats find their own ways to endure, observe, and look after those who share their path.'],
            ['clans', 'windclan', 'WindClan', 'Wide ground and quick feet. WindClan knows the sky is never as far away as it looks.', 'WindClan makes its home under an open sky. Its broad territory invites movement, discovery, and stories shaped by the weather.'],
            ['outsiders', 'kittypets', 'Kittypets', 'Cats whose lives begin alongside people, beyond the four clans.', 'A kittypet may know paths and comforts the clans have never seen. Where that familiarity leads is up to the writers who share the story.'],
            ['outsiders', 'loners', 'Loners', 'Independent cats who make their own way through the territory.', 'Loners live beyond the four allegiances. Some stay close to familiar places; others keep walking to find what comes next.'],
            ['outsiders', 'rogues', 'Rogues', 'Cats whose paths fall outside clan life and its expectations.', 'Rogues bring stories from beyond the borders. Their motives and relationships are shaped by the characters and writers who meet them.'],
        ];

        foreach ($pages as $index => [$kind, $slug, $name, $summary, $intro]) {
            DB::table('world_pages')->insert([
                'kind' => $kind,
                'slug' => $slug,
                'name' => $name,
                'summary' => $summary,
                'eyebrow' => $kind === 'clans' ? 'The four paths' : 'Beyond the clans',
                'title' => $name,
                'intro' => $intro,
                'body' => "## About {$name}\n\n{$summary}\n\n### Begin a story\n\nRead the [guide](/guide) and [site rules](/rules) before building a character. Explore the [character directory](/characters) to see who is already part of the world.",
                'sort_order' => $kind === 'clans' ? $index : $index - 4,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('world_pages');
    }
};