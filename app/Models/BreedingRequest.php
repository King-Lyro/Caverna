<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreedingRequest extends Model
{
    protected $fillable = ['female_character_id', 'male_character_id', 'requested_by', 'status', 'accepted_at'];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    public function female(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'female_character_id');
    }

    public function male(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'male_character_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
