<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_workflow_executions', function (Blueprint $table) {
            $table->foreignId('workflow_template_id')->nullable()->after('workflow_name')->constrained('workflow_templates')->nullOnDelete();
            $table->index(['workflow_template_id', 'agency_id'], 'awe_template_agency_index');
        });
    }

    public function down(): void
    {
        Schema::table('agent_workflow_executions', function (Blueprint $table) {
            $table->dropForeign(['workflow_template_id']);
            $table->dropIndex('awe_template_agency_index');
            $table->dropColumn('workflow_template_id');
        });
    }
};
