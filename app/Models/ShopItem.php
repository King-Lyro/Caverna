<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopItem extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'cost', 'effect', 'is_available'];

    protected function casts(): array
    {
        return ['cost' => 'integer', 'is_available' => 'boolean'];
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }
}
