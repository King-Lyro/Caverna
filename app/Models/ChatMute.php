<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChatMute extends Model
{
    protected $fillable = [
        'user_id', 'guest_hash', 'ip_hash', 'participant_name', 'reason',
        'created_by', 'expires_at', 'revoked_at', 'revoked_by', 'revocation_reason',
    ];

    protected $hidden = ['guest_hash', 'ip_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('revoked_at')->where(function (Builder $query): void {
            $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}
