<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgentWorkflowCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $workflowName,
        public readonly bool $success,
        public readonly string $executionId,
        public readonly ?string $errorMessage = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->success ? 'Completed Successfully' : 'Failed';
        $subject = "Agent Workflow {$status}: {$this->workflowName}";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name}!");

        if ($this->success) {
            $mail->line("Your agent workflow '{$this->workflowName}' has completed successfully.")
                ->line("Execution ID: {$this->executionId}");
        } else {
            $mail->line("Your agent workflow '{$this->workflowName}' has failed.")
                ->line("Execution ID: {$this->executionId}");

            if ($this->errorMessage) {
                $mail->line("Error: {$this->errorMessage}");
            }
        }

        return $mail->line('You can view the full results in your dashboard.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'workflow_name' => $this->workflowName,
            'execution_id' => $this->executionId,
            'success' => $this->success,
            'error_message' => $this->errorMessage,
            'message' => $this->success
                ? "Workflow '{$this->workflowName}' completed successfully."
                : "Workflow '{$this->workflowName}' failed.",
        ];
    }
}
