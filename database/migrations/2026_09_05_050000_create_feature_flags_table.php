<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('feature_key')->unique();
            $table->string('feature_name');
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedTinyInteger('required_plan')->default(0);
            $table->string('minimum_version')->nullable();
            $table->json('allowed_roles')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'enabled', 'required_plan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};
