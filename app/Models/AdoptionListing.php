<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdoptionListing extends Model
{
    use HasFactory;

    protected $fillable = ['character_id', 'owner_id', 'title', 'description', 'images', 'claim_policy', 'status', 'eligibility', 'birthplace', 'parents', 'size_build', 'coloration', 'eyes', 'siblings', 'spirit_symbol', 'appearance', 'personality', 'history', 'adopter_notes', 'contact_instructions', 'claimed_by', 'claimed_at'];

    protected function casts(): array
    {
        return ['images' => 'array', 'claimed_at' => 'datetime'];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(AdoptionApplication::class);
    }
}
