<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->unsignedInteger('free_posts_limit')->default(20)->after('subscription_plan');
            $table->unsignedInteger('free_ai_limit')->default(10)->after('free_posts_limit');
            $table->unsignedInteger('free_accounts_limit')->default(1)->after('free_ai_limit');
            $table->unsignedInteger('free_team_limit')->default(1)->after('free_accounts_limit');
            $table->unsignedInteger('free_clients_limit')->default(1)->after('free_team_limit');
        });

        // Update existing free tier agencies
        DB::table('agencies')
            ->where('subscription_plan', 'free')
            ->update([
                'free_posts_limit' => 20,
                'free_ai_limit' => 10,
                'free_accounts_limit' => 1,
                'free_team_limit' => 1,
                'free_clients_limit' => 1,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn([
                'free_posts_limit',
                'free_ai_limit',
                'free_accounts_limit',
                'free_team_limit',
                'free_clients_limit',
            ]);
        });
    }
};
