<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adoption_listings', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('character_id')->constrained('users')->nullOnDelete();
            $table->string('birthplace')->nullable()->after('description');
            $table->string('parents')->nullable()->after('birthplace');
            $table->string('size_build')->nullable()->after('parents');
            $table->text('coloration')->nullable()->after('size_build');
            $table->string('eyes')->nullable()->after('coloration');
            $table->text('siblings')->nullable()->after('eyes');
            $table->string('spirit_symbol')->nullable()->after('siblings');
            $table->text('appearance')->nullable()->after('spirit_symbol');
            $table->text('personality')->nullable()->after('appearance');
            $table->text('history')->nullable()->after('personality');
            $table->text('adopter_notes')->nullable()->after('history');
            $table->text('contact_instructions')->nullable()->after('adopter_notes');
        });
    }

    public function down(): void
    {
        Schema::table('adoption_listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropColumn(['birthplace', 'parents', 'size_build', 'coloration', 'eyes', 'siblings', 'spirit_symbol', 'appearance', 'personality', 'history', 'adopter_notes', 'contact_instructions']);
        });
    }
};
