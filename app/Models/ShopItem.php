<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopItem extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'cost', 'effect', 'is_available'];

    public function iconUrl(): ?string
    {
        $file = match ($this->effect) {
            'Rare eye color' => 'rare_eye_color.png',
            'Disability' => 'dsiability.png',
            'Energy return' => 'energy_return.png',
            'Energy recover' => 'energy_recover.png',
            'Outsider access' => 'outsider_allegiance.png',
            'Male calico' => 'male_calico.png',
            'Chimera/mosaicism' => 'chimera_mosaicism.png',
            'Karpati/Roan/Salmiak' => 'karpati_roan_salmiak.png',
            'White sepia' => 'white_sepia.png',
            'Albino' => 'albino.png',
            'Purebred' => 'purebred.png',
            default => null,
        };

        return $file && is_file(public_path('images/icons/'.$file)) ? asset('images/icons/'.$file) : null;
    }

    protected function casts(): array
    {
        return ['cost' => 'integer', 'is_available' => 'boolean'];
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }
}
