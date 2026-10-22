<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('commentable'); // polymorphic: posts, campaigns, clients, etc.
            $table->text('body');
            $table->json('mentions')->nullable(); // user IDs mentioned
            $table->foreignId('parent_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'commentable_type', 'commentable_id']);
        });

        Schema::create('activity_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action'); // created_post, published_campaign, etc.
            $table->morphs('subject'); // what was acted upon
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'created_at']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
        Schema::dropIfExists('activity_feeds');
        Schema::dropIfExists('notifications');
    }
};
