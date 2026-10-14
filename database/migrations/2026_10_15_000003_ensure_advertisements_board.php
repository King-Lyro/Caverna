<?php

use App\Models\ForumBoard;
use App\Models\ForumCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $board = ForumBoard::where('slug', 'advertisements')->orWhere('name', 'Advertisements')->first();
        if ($board) {
            $board->update(['is_ic' => false]);

            return;
        }

        $category = ForumCategory::firstOrCreate(['name' => 'The world'], [
            'description' => 'Community news and conversation.',
            'sort_order' => 1,
        ]);
        $category->boards()->create([
            'name' => 'Advertisements',
            'slug' => 'advertisements',
            'description' => 'Share your community. Guests are welcome to post and reply.',
            'is_ic' => false,
            'sort_order' => 4,
        ]);
    }

    public function down(): void
    {
        // Keep the public board and its posts when rolling back.
    }
};
