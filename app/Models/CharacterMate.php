<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterMate extends Model
{
    protected $fillable = ['character_id', 'mate_character_id', 'accepted_at'];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function mateCharacter(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'mate_character_id');
    }
}
