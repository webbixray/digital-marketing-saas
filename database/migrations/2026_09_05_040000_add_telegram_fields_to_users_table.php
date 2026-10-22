<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->unique()->after('remember_token');
            $table->string('telegram_username')->nullable()->after('telegram_chat_id');
            $table->string('telegram_link_code', 32)->nullable()->after('telegram_username');
            $table->timestamp('telegram_linked_at')->nullable()->after('telegram_link_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_chat_id',
                'telegram_username',
                'telegram_link_code',
                'telegram_linked_at',
            ]);
        });
    }
};
