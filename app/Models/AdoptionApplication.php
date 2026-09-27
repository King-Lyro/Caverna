<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdoptionApplication extends Model
{
    use HasFactory;

    protected $fillable = ['adoption_listing_id', 'user_id', 'message', 'status', 'reviewed_by', 'reviewer_notes', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(AdoptionListing::class, 'adoption_listing_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
