<?php

use App\Support\SitePresentation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_themes', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name', 120);
            $table->boolean('is_builtin')->default(false);
            $table->json('colors');
            $table->json('presentation');
            $table->timestamps();
        });
        foreach (SitePresentation::palettes() as $key => $colors) {
            DB::table('site_themes')->insert([
                'key' => $key, 'name' => ucfirst($key), 'is_builtin' => true,
                'colors' => json_encode($colors, JSON_THROW_ON_ERROR),
                'presentation' => '{}', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        Schema::create('site_theme_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_theme_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('recurrence', 10);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('starts_md', 5)->nullable();
            $table->string('ends_md', 5)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
        DB::table('site_settings')->insertOrIgnore([
            'key' => 'theme_scheduling', 'value' => '1',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Cache::forget('site_theme');
    }

    public function down(): void
    {
        Schema::dropIfExists('site_theme_schedules');
        Schema::dropIfExists('site_themes');
        DB::table('site_settings')->whereIn('key', ['theme_scheduling', 'site_presentation'])->delete();
        Cache::forget('site_theme');
    }
};
