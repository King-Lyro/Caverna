<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CricketLedgerEntry extends Model
{
    protected $table = 'cricket_ledger';

    protected $fillable = ['user_id', 'amount', 'type', 'description', 'reference_type', 'reference_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
