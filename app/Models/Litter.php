<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Litter extends Model
{
    protected $fillable = ['pregnancy_id', 'kit_count', 'surviving_count', 'born_at'];

    protected function casts(): array
    {
        return ['kit_count' => 'integer', 'surviving_count' => 'integer', 'born_at' => 'datetime'];
    }

    public function pregnancy(): BelongsTo
    {
        return $this->belongsTo(Pregnancy::class);
    }

    public function kits(): HasMany
    {
        return $this->hasMany(LitterKit::class);
    }
}
