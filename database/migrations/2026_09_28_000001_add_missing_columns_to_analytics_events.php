<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            if (! Schema::hasColumn('analytics_events', 'event_data')) {
                $table->json('event_data')->nullable()->after('event_type');
            }
            if (! Schema::hasColumn('analytics_events', 'platform')) {
                $table->string('platform', 50)->nullable()->after('event_data');
            }
            if (! Schema::hasColumn('analytics_events', 'social_post_id')) {
                $table->foreignId('social_post_id')
                    ->nullable()
                    ->constrained('social_posts')
                    ->nullOnDelete()
                    ->after('platform');
            }
            if (! Schema::hasColumn('analytics_events', 'metadata')) {
                $table->json('metadata')->nullable()->after('social_post_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropColumn(['event_data', 'platform', 'social_post_id', 'metadata']);
        });
    }
};
