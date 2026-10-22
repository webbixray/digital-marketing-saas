<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_post_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform', 50);
            $table->string('platform_comment_id')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_id')->nullable();
            $table->text('content');
            $table->foreignId('parent_id')->nullable()->constrained('social_comments')->nullOnDelete();
            $table->boolean('is_replied')->default(false);
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'platform']);
            $table->index(['social_post_id', 'is_replied']);
            $table->index('platform_comment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_comments');
    }
};
