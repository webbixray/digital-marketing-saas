<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('white_label_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('custom_domain')->nullable()->unique();
            $table->string('brand_name')->nullable();
            $table->string('brand_color', 7)->nullable();
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->text('custom_css')->nullable();
            $table->text('email_signature')->nullable();
            $table->boolean('hide_powered_by')->default(false);
            $table->boolean('enabled')->default(false);
            $table->timestamps();

            $table->index('custom_domain');
        });

        Schema::create('custom_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // email, landing_page, form
            $table->json('content');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('white_label_settings');
        Schema::dropIfExists('custom_templates');
    }
};
