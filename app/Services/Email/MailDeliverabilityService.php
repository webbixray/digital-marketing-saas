<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailDeliverabilityService
{
    public function isConfigured(): bool
    {
        $mailer = config('mail.default');
        $host = config('mail.mailers.smtp.host');

        return $mailer !== 'log' && ! empty($host) && $host !== '127.0.0.1';
    }

    public function getStatus(): array
    {
        return [
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'configured' => $this->isConfigured(),
        ];
    }

    public function sendTestEmail(string $to): bool
    {
        try {
            Mail::raw('This is a test email from Digital Marketing SaaS.', function ($message) use ($to) {
                $message->to($to)->subject('Test Email - Digital Marketing SaaS');
            });

            Log::info('Test email sent successfully', ['to' => $to]);

            return true;
        } catch (\Exception $e) {
            Log::error('Test email failed', ['to' => $to, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function hasValidMxRecords(string $domain): bool
    {
        return checkdnsrr($domain, 'MX');
    }

    public function getHealthCheck(): array
    {
        $status = $this->getStatus();
        $issues = [];

        if ($status['mailer'] === 'log') {
            $issues[] = 'Mail driver is set to log. Emails will not be sent.';
        }

        if (empty($status['host'])) {
            $issues[] = 'SMTP host is not configured.';
        }

        if (empty($status['from_address'])) {
            $issues[] = 'From address is not configured.';
        }

        return [
            'healthy' => empty($issues) && $status['configured'],
            'status' => $status,
            'issues' => $issues,
        ];
    }
}
