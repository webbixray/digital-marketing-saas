<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('social_posts', 'calendar_slot')) {
            Schema::table('social_posts', function (Blueprint $table) {
                $table->json('calendar_slot')->nullable()->after('scheduled_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('social_posts', 'calendar_slot')) {
            Schema::table('social_posts', function (Blueprint $table) {
                $table->dropColumn('calendar_slot');
            });
        }
    }
};
