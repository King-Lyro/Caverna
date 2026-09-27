<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Character extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'sex', 'age_moons', 'allegiance', 'role', 'energy', 'status', 'health_status', 'ailments', 'traits', 'care_updated_at', 'images', 'avatar_path', 'mate', 'kits', 'looks', 'appearance', 'personality', 'history', 'adopted', 'last_ic_post_at'];

    protected function casts(): array
    {
        return ['age_moons' => 'decimal:1', 'energy' => 'integer', 'images' => 'array', 'ailments' => 'array', 'traits' => 'array', 'adopted' => 'boolean', 'last_ic_post_at' => 'datetime', 'inactive_at' => 'datetime', 'died_at' => 'datetime', 'care_updated_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEnergyPercentAttribute(): int
    {
        return $this->energy;
    }
}
