<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'role_id']);
        });

        $now = now();
        DB::table('roles')->insert([
            ['name' => 'Registered', 'slug' => 'registered', 'description' => 'Approved community members.', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Moderator', 'slug' => 'moderator', 'description' => 'Members trusted with moderation tools.', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Members trusted with site administration.', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $roles = DB::table('roles')->pluck('id', 'slug');
        DB::table('users')->orderBy('id')->each(function (object $user) use ($roles, $now): void {
            $slug = $user->role === 'member' ? 'registered' : ($user->role ?: 'registered');
            $roleId = $roles[$slug] ?? $roles['registered'];
            DB::table('user_roles')->insertOrIgnore([
                'user_id' => $user->id,
                'role_id' => $roleId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
    }
};
