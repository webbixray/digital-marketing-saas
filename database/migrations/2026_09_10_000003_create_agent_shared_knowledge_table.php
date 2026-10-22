<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_shared_knowledge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->nullable()->index();
            $table->string('from_agent');
            $table->text('insight');
            $table->string('category');
            $table->decimal('confidence', 5, 4)->default(1.0000);
            $table->timestamps();

            $table->index(['agency_id', 'category']);
            $table->index(['agency_id', 'from_agent']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_shared_knowledge');
    }
};
