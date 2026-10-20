<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteTheme extends Model
{
    protected $fillable = ['key', 'name', 'is_builtin', 'colors', 'presentation'];

    protected function casts(): array
    {
        return ['is_builtin' => 'boolean', 'colors' => 'array', 'presentation' => 'array'];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(SiteThemeSchedule::class);
    }
}
