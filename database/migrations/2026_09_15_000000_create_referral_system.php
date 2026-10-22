<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 20)->unique()->nullable()->after('id');
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete()->after('referral_code');
            $table->decimal('credits', 10, 2)->default(0)->after('referred_by');
            $table->integer('referral_count')->default(0)->after('credits');
            $table->timestamp('first_paid_at')->nullable()->after('referral_count');
        });

        Schema::table('agencies', function (Blueprint $table) {
            $table->string('referral_code', 20)->unique()->nullable()->after('id');
            $table->foreignId('referred_by')->nullable()->constrained('agencies')->nullOnDelete()->after('referral_code');
            $table->decimal('credits', 10, 2)->default(0)->after('referred_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['referral_code', 'referred_by', 'credits', 'referral_count', 'first_paid_at']);
        });

        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['referral_code', 'referred_by', 'credits']);
        });
    }
};
