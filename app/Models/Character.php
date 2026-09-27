<?php

namespace App\Models;

use App\Support\CavernasRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'sex', 'eye_color', 'age_moons', 'allegiance', 'role', 'role_locked', 'energy', 'status', 'is_frozen', 'frozen_reason', 'health_status', 'disability', 'male_calico', 'chimera_mosaicism', 'karpati_roan_salmiak', 'white_sepia', 'albino', 'purebred', 'ailments', 'traits', 'care_updated_at', 'images', 'avatar_path', 'forum_avatar_path', 'mate', 'kits', 'looks', 'appearance', 'personality', 'history', 'adopted', 'last_ic_post_at', 'archived_at'];

    protected function casts(): array
    {
        return ['age_moons' => 'decimal:1', 'energy' => 'integer', 'images' => 'array', 'ailments' => 'array', 'traits' => 'array', 'adopted' => 'boolean', 'role_locked' => 'boolean', 'is_frozen' => 'boolean', 'male_calico' => 'boolean', 'chimera_mosaicism' => 'boolean', 'karpati_roan_salmiak' => 'boolean', 'white_sepia' => 'boolean', 'albino' => 'boolean', 'purebred' => 'boolean', 'last_ic_post_at' => 'datetime', 'inactive_at' => 'datetime', 'died_at' => 'datetime', 'archived_at' => 'datetime', 'care_updated_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mates(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'character_mates', 'character_id', 'mate_character_id')->withPivot('accepted_at')->withTimestamps();
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(CharacterRelationship::class);
    }

    public function appliedItems(): HasMany
    {
        return $this->hasMany(CharacterItem::class);
    }

    public function saleListings(): HasMany
    {
        return $this->hasMany(CharacterSaleListing::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(CharacterTransfer::class);
    }

    public function mentors(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'character_relationships', 'character_id', 'related_character_id')->wherePivot('type', 'mentor')->wherePivot('status', 'accepted')->withPivot('accepted_at');
    }

    public function apprentices(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'character_relationships', 'related_character_id', 'character_id')->wherePivot('type', 'mentor')->wherePivot('status', 'accepted')->withPivot('accepted_at');
    }

    public function parentRelationships(): HasMany
    {
        return $this->hasMany(CharacterRelationship::class)->where('type', 'parent')->where('status', 'accepted');
    }

    public function getEnergyPercentAttribute(): int
    {
        return $this->energy;
    }

    public function calculatedRole(): string
    {
        return CavernasRules::roleForAge((float) $this->age_moons, $this->allegiance);
    }
}
