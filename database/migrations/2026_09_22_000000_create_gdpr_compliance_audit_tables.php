<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gdpr_compliance_audits')) {
            Schema::create('gdpr_compliance_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action', 100);
                $table->string('category', 50)->default('gdpr');
                $table->string('subject_type', 100)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->json('metadata')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->string('severity', 20)->default('info');
                $table->timestamps();
                $table->index(['agency_id', 'created_at']);
                $table->index(['category', 'created_at']);
                $table->index('action');
            });
        }

        // Add expiry_at to consent_records for automated consent expiry
        if (Schema::hasTable('consent_records')) {
            Schema::table('consent_records', function (Blueprint $table) {
                if (! Schema::hasColumn('consent_records', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable()->after('user_agent');
                    $table->index('expires_at');
                }
            });
        }

        // Add ccpa_opt_out to users
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'ccpa_opt_out')) {
                    $table->boolean('ccpa_opt_out')->default(false)->after('notes');
                }
                if (! Schema::hasColumn('users', 'ccpa_opt_out_at')) {
                    $table->timestamp('ccpa_opt_out_at')->nullable()->after('ccpa_opt_out');
                }
            });
        }

        // Add data_retention_days to agencies
        if (Schema::hasTable('agencies')) {
            Schema::table('agencies', function (Blueprint $table) {
                if (! Schema::hasColumn('agencies', 'data_retention_days')) {
                    $table->integer('data_retention_days')->default(365)->after('branding');
                }
                if (! Schema::hasColumn('agencies', 'last_retention_cleanup_at')) {
                    $table->timestamp('last_retention_cleanup_at')->nullable()->after('data_retention_days');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gdpr_compliance_audits');

        Schema::table('consent_records', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ccpa_opt_out', 'ccpa_opt_out_at']);
        });

        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['data_retention_days', 'last_retention_cleanup_at']);
        });
    }
};
