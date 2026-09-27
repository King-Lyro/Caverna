<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterSaleListing extends Model
{
    protected $fillable = ['character_id', 'owner_id', 'price', 'status', 'buyer_id', 'sold_at'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'sold_at' => 'datetime'];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
}
