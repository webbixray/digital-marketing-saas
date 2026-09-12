<?php

namespace App\Listeners;

use App\Events\AgentWorkflowCompleted;
use App\Models\User;
use App\Notifications\AgentWorkflowCompletedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendWorkflowNotificationListener
{
    /**
     * Handle the event.
     */
    public function handle(AgentWorkflowCompleted $event): void
    {
        $user = User::find($event->userId);

        if ($user === null) {
            Log::warning("SendWorkflowNotificationListener: user [{$event->userId}] not found for workflow notification");

            return;
        }

        try {
            Notification::send($user, new AgentWorkflowCompletedNotification(
                workflowName: $event->workflowName,
                success: $event->success,
                executionId: $event->execution->execution_id,
                errorMessage: $event->errorMessage,
            ));

            Log::info("SendWorkflowNotificationListener: notification sent to user [{$event->userId}] for workflow [{$event->workflowName}]");
        } catch (\Exception $e) {
            Log::error("SendWorkflowNotificationListener: failed to send notification: {$e->getMessage()}");
        }
    }
}
