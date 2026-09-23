<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TranslationCompleteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly int $totalItems,
        public readonly int $successCount,
        public readonly int $errorCount,
        public readonly string $targetLang,
        public readonly float $totalCost,
        public readonly bool $failed = false,
        public readonly ?string $errorMessage = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $targetName = $this->getLanguageName($this->targetLang);

        if ($this->failed) {
            $mail = (new MailMessage)
                ->subject('Translation Failed')
                ->greeting("Hello {$notifiable->name}!")
                ->line('Your batch translation job has failed.')
                ->line("Target language: {$targetName}")
                ->line("Items attempted: {$this->totalItems}");

            if ($this->errorMessage) {
                $mail->line("Error: {$this->errorMessage}");
            }

            return $mail->line('Please try again or contact support if the issue persists.');
        }

        $mail = (new MailMessage)
            ->subject('Translation Complete')
            ->greeting("Hello {$notifiable->name}!")
            ->line('Your batch translation job has completed.')
            ->line("Target language: {$targetName}")
            ->line("Items processed: {$this->successCount} of {$this->totalItems}")
            ->line('Cost: $' . number_format($this->totalCost, 4));

        if ($this->errorCount > 0) {
            $mail->line("Errors: {$this->errorCount} items failed");
        }

        return $mail->line('You can view the translations in your dashboard.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $targetName = $this->getLanguageName($this->targetLang);

        return [
            'type' => 'translation_complete',
            'target_lang' => $this->targetLang,
            'target_lang_name' => $targetName,
            'total_items' => $this->totalItems,
            'success_count' => $this->successCount,
            'error_count' => $this->errorCount,
            'total_cost' => $this->totalCost,
            'failed' => $this->failed,
            'error_message' => $this->errorMessage,
            'message' => $this->failed
                ? "Translation to {$targetName} failed."
                : "Successfully translated {$this->successCount} of {$this->totalItems} items to {$targetName}.",
        ];
    }

    /**
     * Get language name from code.
     */
    protected function getLanguageName(string $code): string
    {
        $languages = [
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'nl' => 'Dutch',
            'ru' => 'Russian',
            'zh' => 'Chinese',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'ar' => 'Arabic',
            'hi' => 'Hindi',
            'tr' => 'Turkish',
            'pl' => 'Polish',
            'sv' => 'Swedish',
            'da' => 'Danish',
            'fi' => 'Finnish',
            'no' => 'Norwegian',
            'th' => 'Thai',
            'vi' => 'Vietnamese',
            'id' => 'Indonesian',
            'ms' => 'Malay',
        ];

        return $languages[$code] ?? $code;
    }
}
