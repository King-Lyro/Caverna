<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('content_pages')->insertOrIgnore([
            'slug' => 'adoption',
            'eyebrow' => 'Find a new story',
            'title' => "Adoption\ncandidates.",
            'intro' => 'Meet characters prepared for a new home. Some listings invite an application; others can be claimed immediately.',
            'body' => <<<'MARKDOWN'
## Find a story to carry forward

Each listing represents a character whose next chapter is ready to be written. Read their history and any notes from the current owner before making a choice.

### Adoption paths

| Listing type | Next step |
| --- | --- |
| Instant claim | An eligible member can take the character into their care immediately. |
| Application | Share how you would continue the character's story and wait for the owner to review it. |

Visit a listing to see the character's full details. Review the [site rules](/rules) and [guide](/guide) before joining a new story.
MARKDOWN,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
    }
};