<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('metered_usages')) {
            Schema::create('metered_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
                $table->string('metric', 50); // ai_tokens, api_calls, storage_gb, etc.
                $table->decimal('quantity', 12, 4)->default(0);
                $table->decimal('unit_price', 10, 8)->default(0);
                $table->decimal('total_price', 12, 4)->default(0);
                $table->timestamp('recorded_at')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['agency_id', 'metric']);
                $table->index(['agency_id', 'recorded_at']);
                $table->index('metric');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('metered_usages');
    }
};
