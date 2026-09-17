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
        Schema::table('agencies', function (Blueprint $table) {
            $table->unsignedInteger('ai_credits')->default(0)->after('credits');
            $table->unsignedInteger('ai_credits_purchased')->default(0)->after('ai_credits');
        });

        // Create AI credit purchase logs
        Schema::create('ai_credit_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('credits');
            $table->decimal('amount', 10, 2);
            $table->string('stripe_payment_id')->nullable();
            $table->string('status')->default('completed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_credit_purchases');

        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['ai_credits', 'ai_credits_purchased']);
        });
    }
};
