<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $pregnancies = DB::table('pregnancies')->where('status', 'pregnant')->get();
            foreach ($pregnancies as $pregnancy) {
                DB::table('pregnancies')->where('id', $pregnancy->id)
                    ->update(['due_at' => Carbon::parse($pregnancy->due_at)->startOfDay()]);
                $female = DB::table('characters')->where('id', $pregnancy->female_character_id)->first();
                if (! $female || $female->status === 'deceased' || $female->role === 'queen') {
                    continue;
                }
                if (! $pregnancy->previous_female_role) {
                    DB::table('pregnancies')->where('id', $pregnancy->id)
                        ->update(['previous_female_role' => $female->role]);
                }
                DB::table('characters')->where('id', $female->id)->update(['role' => 'queen']);
            }
        });
    }

    public function down(): void
    {
        // Preserve pregnancy role history and current lifecycle state.
    }
};
