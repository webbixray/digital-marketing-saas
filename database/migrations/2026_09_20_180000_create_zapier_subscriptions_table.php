<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zapier_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('webhook_url');
            $table->string('trigger_type');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('agency_id');
            $table->index('trigger_type');
            $table->index(['agency_id', 'trigger_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zapier_subscriptions');
    }
};
