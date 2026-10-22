<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
                $table->string('name', 255);
                $table->string('slug', 255);
                $table->text('description')->nullable();
                $table->string('sku', 100)->nullable();
                $table->decimal('price', 10, 2)->default(0);
                $table->decimal('sale_price', 10, 2)->nullable();
                $table->string('currency', 3)->default('USD');
                $table->integer('inventory_count')->default(0);
                $table->integer('low_stock_threshold')->default(5);
                $table->enum('status', ['active', 'draft', 'discontinued'])->default('draft');
                $table->json('images')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['agency_id', 'slug']);
                $table->index(['agency_id', 'status']);
                $table->index(['agency_id', 'sku']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
