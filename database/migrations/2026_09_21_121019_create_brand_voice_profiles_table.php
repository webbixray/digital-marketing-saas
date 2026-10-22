<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('brand_voice_profiles')) {
            Schema::create('brand_voice_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->constrained()->onDelete('cascade');
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->json('samples')->nullable();
                $table->json('analysis')->nullable();
                $table->json('tone_attributes')->nullable();
                $table->json('vocabulary_patterns')->nullable();
                $table->json('sentence_structure')->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->index(['agency_id', 'is_default']);
            });
        }

        // Add brand_voice_id to ai_content_logs
        if (! Schema::hasColumn('ai_content_logs', 'brand_voice_id')) {
            Schema::table('ai_content_logs', function (Blueprint $table) {
                $table->foreignId('brand_voice_id')->nullable()->after('agency_id')->constrained('brand_voice_profiles')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ai_content_logs', 'brand_voice_id')) {
            Schema::table('ai_content_logs', function (Blueprint $table) {
                $table->dropForeign(['brand_voice_id']);
                $table->dropColumn('brand_voice_id');
            });
        }
        Schema::dropIfExists('brand_voice_profiles');
    }
};
