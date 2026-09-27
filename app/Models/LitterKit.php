<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LitterKit extends Model
{
    protected $fillable = ['litter_id', 'status', 'sex', 'coat_notes', 'has_disability', 'owner_id'];

    protected function casts(): array
    {
        return ['has_disability' => 'boolean'];
    }

    public function litter(): BelongsTo
    {
        return $this->belongsTo(Litter::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
