<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('client_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->json('report_data');
            $table->string('period'); // monthly, quarterly, yearly
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('draft');
            $table->string('access_token')->unique();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'client_id']);
            $table->index('access_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_reports');
    }
};
