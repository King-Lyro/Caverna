<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pregnancy extends Model
{
    protected $fillable = ['female_character_id', 'male_character_id', 'previous_female_role', 'mates', 'conceived_at', 'due_at', 'status'];

    protected function casts(): array
    {
        return ['mates' => 'boolean', 'conceived_at' => 'datetime', 'due_at' => 'datetime'];
    }

    public function female(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'female_character_id');
    }

    public function male(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'male_character_id');
    }

    public function litter(): HasOne
    {
        return $this->hasOne(Litter::class);
    }
}
