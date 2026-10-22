<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Workflows (automation rules)
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('draft'); // draft, active, paused
            $table->string('trigger_type')->nullable(); // new_post, post_published, comment_received, mention_received, message_received, schedule, cron
            $table->json('trigger_config')->nullable();
            $table->json('actions')->nullable(); // array of action definitions
            $table->json('conditions')->nullable(); // array of condition rules
            $table->unsignedInteger('execution_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->string('error_message')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'status']);
            $table->index(['trigger_type']);
        });

        // Workflow Executions (history of each run)
        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, running, success, failed
            $table->json('trigger_data')->nullable();
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->json('action_results')->nullable();
            $table->string('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['workflow_id', 'status']);
            $table->index(['started_at']);
        });

        // Workflow Logs
        Schema::create('workflow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_execution_id')->constrained('workflow_executions')->cascadeOnDelete();
            $table->string('level'); // info, warning, error
            $table->string('message');
            $table->json('context')->nullable();
            $table->timestamps();
            $table->index(['workflow_execution_id']);
        });

        // Social Inbox Messages
        Schema::create('inbox_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->string('platform');
            $table->string('message_id')->nullable(); // ID from platform
            $table->string('message_type')->default('comment'); // comment, mention, direct_message, reply
            $table->string('author_id')->nullable();
            $table->string('author_name');
            $table->string('author_username')->nullable();
            $table->string('author_avatar')->nullable();
            $table->text('content');
            $table->string('parent_id')->nullable(); // for replies
            $table->string('post_id')->nullable(); // associated post
            $table->string('status')->default('unread'); // unread, read, triaged, replied, escalated, archived
            $table->json('metadata')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->string('replied_content')->nullable();
            $table->foreignId('replied_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agency_id', 'platform', 'status']);
            $table->index(['status']);
            $table->index(['received_at']);
            $table->index(['social_account_id']);
        });

        // Inbox Triage Actions (auto-triage results)
        Schema::create('inbox_triage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbox_message_id')->constrained('inbox_messages')->cascadeOnDelete();
            $table->string('action')->nullable(); // auto_reply, auto_triage, escalate, mark_read, mark_important, ignore
            $table->string('sentiment')->nullable(); // positive, negative, neutral
            $table->string('category')->nullable(); // feedback, question, complaint, praise, spam, inquiry
            $table->decimal('urgency_score', 3, 2)->nullable();
            $table->text('ai_analysis')->nullable();
            $table->string('suggested_reply')->nullable();
            $table->timestamp('triage_at')->nullable();
            $table->timestamps();
            $table->unique(['inbox_message_id']);
        });

        // Email Marketing Campaigns
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('newsletter'); // newsletter, promotional, transactional
            $table->string('status')->default('draft'); // draft, scheduled, sending, sent, cancelled
            $table->longText('content')->nullable();
            $table->string('subject')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('reply_to')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('opened_count')->default(0);
            $table->unsignedInteger('clicked_count')->default(0);
            $table->unsignedInteger('bounced_count')->default(0);
            $table->unsignedInteger('unsubscribed_count')->default(0);
            $table->decimal('open_rate', 5, 2)->nullable();
            $table->decimal('click_rate', 5, 2)->nullable();
            $table->decimal('bounce_rate', 5, 2)->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'status']);
            $table->index(['type']);
            $table->index(['scheduled_at']);
        });

        // Email Campaign Recipients
        Schema::create('email_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_campaign_id')->constrained('email_campaigns')->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('status')->default('pending'); // pending, sent, opened, clicked, bounced, unsubscribed
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->index(['email_campaign_id', 'status']);
            $table->unique(['email_campaign_id', 'email']);
        });

        // Landing Pages
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->string('headline')->nullable();
            $table->text('content')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('background_color')->default('#ffffff');
            $table->string('text_color')->default('#333333');
            $table->string('button_color')->default('#007bff');
            $table->string('button_text_color')->default('#ffffff');
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->unsignedInteger('conversions_count')->default(0);
            $table->decimal('conversion_rate', 5, 2)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'is_published']);
            $table->index(['slug']);
        });

        // Form Builder
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('fields')->nullable(); // array of field definitions
            $table->string('success_message')->nullable();
            $table->string('redirect_url')->nullable();
            $table->string('background_color')->default('#ffffff');
            $table->string('text_color')->default('#333333');
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('submissions_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'is_published']);
            $table->index(['slug']);
        });

        // Form Responses
        Schema::create('form_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->json('data')->nullable(); // submitted field values
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->index(['form_id', 'submitted_at']);
        });

        // Invoice Items
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable();
            $table->string('description');
            $table->string('type')->default('line_item'); // line_item, tax, discount, shipping
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_price', 8, 2)->default(0);
            $table->decimal('total', 8, 2)->default(0);
            $table->timestamps();
        });

        // Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('status')->default('draft'); // draft, pending, paid, overdue, cancelled
            $table->string('type')->default('invoice'); // invoice, credit_note
            $table->string('currency')->default('USD');
            $table->decimal('subtotal', 8, 2)->default(0);
            $table->decimal('tax', 8, 2)->default(0);
            $table->decimal('discount', 8, 2)->default(0);
            $table->decimal('total', 8, 2)->default(0);
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index(['invoice_number']);
            $table->index(['issue_date', 'due_date']);
        });

        // Subscriptions (for client billing if agency charges clients)
        Schema::create('client_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('plan_name');
            $table->decimal('price', 8, 2)->default(0);
            $table->string('interval')->default('month');
            $table->string('status')->default('active'); // active, past_due, cancelled, trialing
            $table->timestamp('start_date')->useCurrent();
            $table->timestamp('end_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'client_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_subscriptions');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('form_responses');
        Schema::dropIfExists('forms');
        Schema::dropIfExists('landing_pages');
        Schema::dropIfExists('email_campaign_recipients');
        Schema::dropIfExists('email_campaigns');
        Schema::dropIfExists('inbox_triage');
        Schema::dropIfExists('inbox_messages');
        Schema::dropIfExists('workflow_logs');
        Schema::dropIfExists('workflow_executions');
        Schema::dropIfExists('workflows');
    }
};
