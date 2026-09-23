<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('usage_quotas')) {
            Schema::create('usage_quotas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
                $table->string('metric', 50);
                $table->integer('limit')->default(0);
                $table->integer('used')->default(0);
                $table->string('period', 20)->default('monthly'); // monthly, lifetime
                $table->timestamp('reset_at')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['agency_id', 'metric']);
                $table->index(['agency_id', 'metric']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_quotas');
    }
};
