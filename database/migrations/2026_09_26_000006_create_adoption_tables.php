<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adoption_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->json('images')->nullable();
            $table->string('claim_policy')->default('application');
            $table->string('status')->default('available');
            $table->text('eligibility')->nullable();
            $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('claimed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'claim_policy']);
        });

        Schema::create('adoption_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adoption_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reviewer_notes')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['adoption_listing_id', 'user_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adoption_applications');
        Schema::dropIfExists('adoption_listings');
    }
};
