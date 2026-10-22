<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'trigger_type' => 'required|in:new_post,post_published,post_failed,comment_received,mention_received,message_received,schedule,cron',
            'trigger_config' => 'nullable|array',
            'actions' => 'required|array|min:1',
            'actions.*.type' => 'required|in:send_notification,auto_reply,create_post,schedule_post,ai_generate,ai_reply,tag_client,update_campaign,send_email,webhook,sleep',
            'actions.*.config' => 'nullable|array',
            'conditions' => 'nullable|array',
        ];
    }
}
