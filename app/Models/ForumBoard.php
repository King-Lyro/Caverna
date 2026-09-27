<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumBoard extends Model
{
    use HasFactory;

    protected $fillable = ['forum_category_id', 'name', 'slug', 'description', 'is_ic', 'sort_order'];

    protected function casts(): array
    {
        return ['is_ic' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'forum_category_id');
    }

    public function threads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }
}
