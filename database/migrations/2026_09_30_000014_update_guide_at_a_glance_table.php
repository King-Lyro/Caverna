<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $guide = DB::table('content_pages')->where('slug', 'guide')->first();
        if (! $guide) {
            return;
        }

        $body = str_replace(
            <<<'OLD'
### At a glance

| Starting point | What to know |
| --- | --- |
| First characters | Three non-adopted characters are free. |
| Age | Characters must be at least six moons old. |
| In-character writing | Posts should be at least 70 words. |
| Keeping active | Make an in-character post within each 30-day activity window. |
OLD,
            <<<'NEW'
### At a glance

| Starting point | What to know |
| --- | --- |
| First characters | Your first three non-adopted characters are free; every one after costs 200 crickets. Adopted characters never cost crickets. |
| Age | Characters must be at least six moons old, and age cannot be changed after submission. |
| In-character writing | Posts should be at least 70 words. IC replies earn 10 crickets, new IC threads earn 20. |
| Energy | Characters start at 100 energy. A post at home restores 20 energy; posting outside home territory costs 10. Energy lost to a week of silence is 20, and reaching 0 makes a character inactive. |
| Keeping active | Make an in-character post within each 30-day activity window, or risk your character becoming inactive and, eventually, deceased. |
| Mates & breeding | Cats need to be 12+ moons old to take a mate or breed; most roles breed freely, though Leaders, Deputies, and Medicine Cats carry a cricket cost. |
NEW,
            $guide->body
        );

        DB::table('content_pages')->where('slug', 'guide')->update(['body' => $body, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Intentionally left as a one-way content correction.
    }
};
