<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->text('description')->nullable()->after('guard_name');
            $table->boolean('is_system')->default(false)->after('description');
            $table->unsignedInteger('users_count')->default(0)->after('is_system');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('guard_name');
            $table->foreign('category_id')->references('id')->on('permission_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['description', 'is_system', 'users_count']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
