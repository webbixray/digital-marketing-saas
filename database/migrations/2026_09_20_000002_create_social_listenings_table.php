<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('social_listenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->string('keyword');
            $table->string('platform')->default('all'); // all, twitter, facebook, instagram, linkedin, tiktok
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedInteger('match_count')->default(0);
            $table->unsignedInteger('sentiment_positive')->default(0);
            $table->unsignedInteger('sentiment_negative')->default(0);
            $table->unsignedInteger('sentiment_neutral')->default(0);
            $table->timestamps();

            $table->index(['agency_id', 'platform']);
            $table->index(['agency_id', 'is_active']);
            $table->index('last_checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_listenings');
    }
};
