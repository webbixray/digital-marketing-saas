<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('resellers')) {
            Schema::create('resellers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('domain')->unique();
                $table->string('logo_url')->nullable();
                $table->string('primary_color', 7)->nullable();
                $table->boolean('is_active')->default(true);
                $table->decimal('commission_rate', 8, 2)->default(0);
                $table->string('commission_type', 20)->default('percentage');
                $table->string('billing_type', 20)->default('revenue_share');
                $table->json('settings')->nullable();
                $table->timestamps();

                $table->index('agency_id');
                $table->index('is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resellers');
    }
};
