<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SidebarModule extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'body', 'link_text', 'link_url', 'placement', 'sort_order', 'is_enabled'];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean', 'sort_order' => 'integer'];
    }
}
