<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MateRequest extends Model
{
    protected $fillable = ['from_character_id', 'to_character_id', 'status'];

    public function fromCharacter(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'from_character_id');
    }

    public function toCharacter(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'to_character_id');
    }
}
