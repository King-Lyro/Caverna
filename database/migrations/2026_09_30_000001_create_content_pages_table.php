<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('eyebrow');
            $table->string('title');
            $table->text('intro');
            $table->longText('body');
            $table->timestamps();
        });

        DB::table('content_pages')->insert([
            'slug' => 'guide',
            'eyebrow' => 'Your first path',
            'title' => "A guide for\nnew paws.",
            'intro' => 'Start with the rules, choose an allegiance, then build the character you want to follow through the seasons.',
            'body' => <<<'MARKDOWN'
## Begin in Cavernas

Cavernas is a shared role-playing world. Read the [site rules](/rules) before you join a story, and leave space for other writers to shape what happens next.

### Find your place

Four clans share the territory. Each has its own paths and traditions; your character's allegiance is the beginning of a story, not its ending.

| Allegiance | A place to start |
| --- | --- |
| ThunderClan | A life among clearings and woodland paths. |
| RiverClan | Follow the water and the stories along its banks. |
| ShadowClan | Find your footing beneath the pines. |
| WindClan | Cross open ground beneath a wide sky. |

Explore the [clans](/clans) and the [territory](/map) before deciding where your character belongs. Kittypets, loners, and rogues follow different paths; learn about [outsiders](/outsiders) if one of those lives calls to you.

## Make a character

1. Choose an allegiance and a name that fit the world.
2. Write their appearance, personality, and history. What do they want, and what might stand in their way?
3. Add three images to their profile and submit the character for the story ahead.

Your first three non-adopted characters are free. Characters begin at six moons or older. Visit [character creation](/characters/create) when you are ready, or browse [adoption](/adoption) for a life already underway.

### At a glance

| Starting point | What to know |
| --- | --- |
| First characters | Three non-adopted characters are free. |
| Age | Characters must be at least six moons old. |
| In-character writing | Posts should be at least 70 words. |
| Keeping active | Make an in-character post within each 30-day activity window. |

## Join the story

Read the latest conversations in the [forum](/forum), find a board that fits, and invite another writer into a scene. Characters can change, fail, and surprise one another. The best stories leave room for all of that.

### Stay connected

Your [dashboard](/dashboard) brings your characters and activity together. Keep an eye on your stories as the seasons move, and reach out to staff when you need help finding your way.
MARKDOWN,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('content_pages');
    }
};