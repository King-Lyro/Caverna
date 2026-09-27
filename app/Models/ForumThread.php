<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumThread extends Model
{
    use HasFactory;

    protected $fillable = ['forum_board_id', 'user_id', 'title', 'slug', 'is_pinned', 'is_locked'];

    protected function casts(): array
    {
        return ['is_pinned' => 'boolean', 'is_locked' => 'boolean'];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(ForumBoard::class, 'forum_board_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class);
    }
}
