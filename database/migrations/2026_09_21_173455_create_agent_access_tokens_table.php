<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agent_access_tokens')) {
            Schema::create('agent_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
                $table->string('name', 100);
                $table->string('token', 64)->unique();
                $table->json('abilities')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['agency_id', 'is_active']);
                $table->index('token');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_access_tokens');
    }
};
