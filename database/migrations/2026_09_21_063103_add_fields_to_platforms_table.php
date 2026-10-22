<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platforms', function (Blueprint $table) {
            $table->text('description')->nullable()->after('display_name');
            $table->string('icon')->nullable()->after('description');
            $table->string('color')->nullable()->after('icon');
            $table->json('config')->nullable()->after('scopes');
            $table->boolean('is_published')->default(true)->after('is_active');
            $table->integer('sort_order')->default(0)->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('platforms', function (Blueprint $table) {
            $table->dropColumn(['description', 'icon', 'color', 'config', 'is_published', 'sort_order']);
        });
    }
};
