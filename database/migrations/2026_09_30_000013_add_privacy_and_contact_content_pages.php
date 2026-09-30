<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('content_pages')->insert([
            [
                'slug' => 'privacy',
                'eyebrow' => 'Your information',
                'title' => "Privacy at\nCavernas.",
                'intro' => 'How account, profile, and gameplay information is handled.',
                'body' => "This placeholder explains how account, profile, and gameplay information will be handled. Replace it with approved legal copy before launch.",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'contact',
                'eyebrow' => 'Reach the team',
                'title' => "Contact\nCavernas.",
                'intro' => 'How to reach staff for moderation and technical support.',
                'body' => "This placeholder will become the official staff contact instructions, including moderation and technical support paths.",
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('content_pages')->whereIn('slug', ['privacy', 'contact'])->delete();
    }
};
