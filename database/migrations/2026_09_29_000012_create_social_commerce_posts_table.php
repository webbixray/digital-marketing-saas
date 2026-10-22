<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('social_commerce_posts')) {
            Schema::create('social_commerce_posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
                $table->foreignId('social_post_id')->constrained('social_posts')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('shop_url', 500)->nullable();
                $table->string('discount_code', 100)->nullable();
                $table->json('utm_params')->nullable();
                $table->unsignedInteger('clicks')->default(0);
                $table->unsignedInteger('conversions')->default(0);
                $table->decimal('revenue', 10, 2)->default(0);
                $table->timestamp('created_at')->useCurrent();

                $table->index(['agency_id', 'product_id']);
                $table->index(['agency_id', 'social_post_id']);
                $table->index(['agency_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('social_commerce_posts');
    }
};
