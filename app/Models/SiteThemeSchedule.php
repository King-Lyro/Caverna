<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteThemeSchedule extends Model
{
    protected $fillable = ['site_theme_id', 'name', 'recurrence', 'starts_on', 'ends_on', 'starts_md', 'ends_md', 'is_enabled'];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean'];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(SiteTheme::class, 'site_theme_id');
    }
}
