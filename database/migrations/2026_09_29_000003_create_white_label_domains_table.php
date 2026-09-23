<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('white_label_domains')) {
            Schema::create('white_label_domains', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reseller_id')->constrained('resellers')->cascadeOnDelete();
                $table->string('domain')->unique();
                $table->boolean('is_verified')->default(false);
                $table->string('verification_token')->nullable();
                $table->string('ssl_status', 20)->default('pending');
                $table->string('status', 20)->default('pending');
                $table->timestamps();

                $table->index('reseller_id');
                $table->index('domain');
                $table->index('status');
                $table->index('is_verified');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('white_label_domains');
    }
};
