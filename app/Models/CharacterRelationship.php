<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterRelationship extends Model
{
    protected $fillable = ['character_id', 'related_character_id', 'type', 'status', 'requested_by', 'accepted_at'];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function relatedCharacter(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'related_character_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
