<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['Kittypet' => 'kittypet', 'Loner' => 'loner', 'Rogue' => 'rogue'] as $allegiance => $role) {
            DB::table('characters')->where('allegiance', $allegiance)->where('role', $role)->update(['allegiance' => 'outsider']);
        }
    }

    public function down(): void
    {
    }
};