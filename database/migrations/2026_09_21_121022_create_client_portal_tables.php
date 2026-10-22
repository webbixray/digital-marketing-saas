<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_portal_settings')) {
            Schema::create('client_portal_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->constrained()->onDelete('cascade');
                $table->string('brand_name')->nullable();
                $table->string('brand_color', 7)->default('#6366f1');
                $table->string('logo_url')->nullable();
                $table->string('custom_domain')->nullable()->unique();
                $table->boolean('is_enabled')->default(true);
                $table->boolean('show_analytics')->default(true);
                $table->boolean('show_invoices')->default(true);
                $table->boolean('allow_approvals')->default(true);
                $table->boolean('show_team_activity')->default(false);
                $table->string('welcome_message')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('client_access_tokens')) {
            Schema::create('client_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->string('token', 64)->unique();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_access_tokens');
        Schema::dropIfExists('client_portal_settings');
    }
};
