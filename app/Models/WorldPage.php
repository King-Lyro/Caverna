<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorldPage extends Model
{
    protected $fillable = ['kind', 'slug', 'name', 'summary', 'eyebrow', 'title', 'intro', 'body', 'sort_order'];
}