<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LitterKit extends Model
{
    protected $fillable = ['litter_id', 'character_id', 'status', 'sex', 'energy', 'coat_notes', 'has_disability', 'low_health', 'owner_id'];

    protected function casts(): array
    {
        return ['energy' => 'integer', 'has_disability' => 'boolean', 'low_health' => 'boolean'];
    }

    public function litter(): BelongsTo
    {
        return $this->belongsTo(Litter::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
