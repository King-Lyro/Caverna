<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_guest');
            $table->string('author_name', 255);
            $table->string('guest_hash', 64)->nullable()->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['deleted_at', 'id']);
        });

        Schema::create('chat_mutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_hash', 64)->nullable()->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('participant_name');
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();
        });

        DB::table('site_settings')->insertOrIgnore([
            'key' => 'chat_guest_posting',
            'value' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_mutes');
        Schema::dropIfExists('chat_messages');
        DB::table('site_settings')->where('key', 'chat_guest_posting')->delete();
    }
};
