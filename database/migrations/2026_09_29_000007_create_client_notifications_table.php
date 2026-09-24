<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_notifications')) {
            Schema::create('client_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
                $table->string('type', 50)->default('message');
                $table->string('title', 255);
                $table->text('body')->nullable();
                $table->string('action_url')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['client_id', 'created_at'], 'client_notifications_client_created_index');
                $table->index(['client_id', 'is_read'], 'client_notifications_client_read_index');
                $table->index(['type'], 'client_notifications_type_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_notifications');
    }
};
