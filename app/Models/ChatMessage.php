<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'is_guest', 'author_name', 'guest_hash', 'ip_hash', 'body'];

    protected $hidden = ['guest_hash', 'ip_hash'];

    protected function casts(): array
    {
        return ['is_guest' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
