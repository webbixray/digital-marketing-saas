<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('content_genomes')) {
            Schema::create('content_genomes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
                $table->string('platform', 50);
                $table->unsignedInteger('optimal_length')->nullable();
                $table->json('best_hashtags')->nullable();
                $table->json('best_times')->nullable();
                $table->json('content_themes')->nullable();
                $table->json('tone_patterns')->nullable();
                $table->json('media_types')->nullable();
                $table->json('cta_patterns')->nullable();
                $table->json('engagement_prediction')->nullable();
                $table->decimal('accuracy_score', 5, 4)->default(0);
                $table->unsignedInteger('data_points_count')->default(0);
                $table->json('genome_data')->nullable();
                $table->timestamp('last_updated_at')->nullable();
                $table->timestamps();

                $table->index(['agency_id', 'platform']);
                $table->index('accuracy_score');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_genomes');
    }
};
