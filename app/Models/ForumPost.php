<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForumPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['forum_thread_id', 'user_id', 'character_id', 'body', 'is_ic'];

    protected function casts(): array
    {
        return ['is_ic' => 'boolean'];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
