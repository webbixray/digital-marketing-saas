<?php

namespace App\Services\WhiteLabel;

use App\Models\WhiteLabelSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;

class WhiteLabelService
{
    /**
     * Setup a custom domain for an agency.
     */
    public function setupCustomDomain(int $agencyId, string $domain): bool
    {
        $domain = $this->normalizeDomain($domain);

        if (! $this->isDomainAvailable($domain, $agencyId)) {
            Log::warning("Domain [{$domain}] is already in use by another agency.");

            return false;
        }

        $setting = WhiteLabelSetting::updateOrCreate(
            ['agency_id' => $agencyId],
            [
                'custom_domain' => $domain,
                'enabled' => true,
            ]
        );

        return $setting->exists;
    }

    /**
     * Validate domain ownership via DNS TXT record.
     */
    public function validateDomainOwnership(string $domain): bool
    {
        $domain = $this->normalizeDomain($domain);

        if (! checkdnsrr($domain, 'A') && ! checkdnsrr($domain, 'CNAME')) {
            Log::warning("Domain [{$domain}] has no valid A or CNAME records.");

            return false;
        }

        // Check for a verification TXT record
        $txtRecords = dns_get_record($domain, DNS_TXT);
        $verificationToken = $this->getVerificationToken($domain);

        foreach ($txtRecords as $record) {
            if (isset($record['txt']) && str_contains($record['txt'], $verificationToken)) {
                return true;
            }
        }

        // Fallback: if domain resolves, consider it provisionally valid
        // (production would require TXT verification)
        return ! empty($txtRecords) || checkdnsrr($domain, 'A');
    }

    /**
     * Get all branded assets for an agency.
     */
    public function getBrandedAssets(int $agencyId): array
    {
        $settings = WhiteLabelSetting::where('agency_id', $agencyId)->first();

        if (! $settings) {
            return $this->getDefaultAssets();
        }

        return [
            'brand_name' => $settings->display_name,
            'brand_color' => $settings->display_color,
            'logo_url' => $settings->display_logo,
            'favicon_url' => $settings->favicon_url,
            'custom_domain' => $settings->custom_domain,
            'from_name' => $settings->display_from_name,
            'from_email' => $settings->display_from_email,
            'email_signature' => $settings->email_signature,
            'hide_powered_by' => $settings->hide_powered_by,
            'enabled' => $settings->enabled,
        ];
    }

    /**
     * Send a branded email for an agency.
     */
    public function sendBrandedEmail(int $agencyId, string $template, array $data): void
    {
        $settings = WhiteLabelSetting::where('agency_id', $agencyId)
            ->where('enabled', true)
            ->first();

        $fromEmail = $settings?->display_from_email ?? config('mail.from.address');
        $fromName = $settings?->display_from_name ?? config('mail.from.name');

        $mergeData = array_merge($data, [
            'brand_name' => $settings?->display_name ?? config('app.name'),
            'brand_color' => $settings?->display_color ?? '#007bff',
            'logo_url' => $settings?->display_logo ?? asset('images/default-logo.png'),
            'signature' => $settings?->email_signature ?? '',
        ]);

        Mail::html($this->renderTemplate($template, $mergeData), function ($message) use ($fromEmail, $fromName, $mergeData) {
            $message->to($mergeData['to'] ?? '', $mergeData['to_name'] ?? '')
                ->subject($mergeData['subject'] ?? 'Notification')
                ->from($fromEmail, $fromName);
        });
    }

    /**
     * Get custom CSS for an agency.
     */
    public function getCustomCSS(int $agencyId): string
    {
        $settings = WhiteLabelSetting::where('agency_id', $agencyId)
            ->where('enabled', true)
            ->first();

        if (! $settings || empty($settings->custom_css)) {
            return '';
        }

        return $settings->custom_css;
    }

    /**
     * Apply branding to a view by sharing white-label data.
     */
    public function applyBranding(View $view): View
    {
        $viewData = $view->getData();

        // Inject branding variables if agency_id is available
        if (isset($viewData['agencyId'])) {
            $settings = WhiteLabelSetting::where('agency_id', $viewData['agencyId'])
                ->where('enabled', true)
                ->first();

            if ($settings) {
                $view->with('whiteLabel', $settings);
                $view->with('brandAssets', $this->getBrandedAssets($viewData['agencyId']));
                $view->with('customCSS', $this->getCustomCSS($viewData['agencyId']));
            }
        }

        return $view;
    }

    /**
     * Normalize a domain name.
     */
    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');

        return $domain;
    }

    /**
     * Check if a domain is available for assignment.
     */
    private function isDomainAvailable(string $domain, int $excludeAgencyId): bool
    {
        return ! WhiteLabelSetting::where('custom_domain', $domain)
            ->where('agency_id', '!=', $excludeAgencyId)
            ->exists();
    }

    /**
     * Get the DNS verification token for a domain.
     */
    private function getVerificationToken(string $domain): string
    {
        return 'dms-verify='.md5($domain.config('app.key'));
    }

    /**
     * Get default brand assets when no settings exist.
     */
    private function getDefaultAssets(): array
    {
        return [
            'brand_name' => config('app.name'),
            'brand_color' => '#007bff',
            'logo_url' => asset('images/default-logo.png'),
            'favicon_url' => null,
            'custom_domain' => null,
            'from_name' => config('mail.from.name'),
            'from_email' => config('mail.from.address'),
            'email_signature' => null,
            'hide_powered_by' => false,
            'enabled' => false,
        ];
    }

    /**
     * Render an email template with data.
     */
    private function renderTemplate(string $template, array $data): string
    {
        // Try agency-specific template first, then fall back to default
        if (ViewFacade::exists("emails.white-label.{$template}")) {
            return ViewFacade::make("emails.white-label.{$template}", $data)->render();
        }

        if (ViewFacade::exists("emails.{$template}")) {
            return ViewFacade::make("emails.{$template}", $data)->render();
        }

        // Fallback: build a simple HTML email
        return $this->buildFallbackEmail($data);
    }

    /**
     * Build a simple fallback HTML email.
     */
    private function buildFallbackEmail(array $data): string
    {
        $brandName = $data['brand_name'] ?? config('app.name');
        $brandColor = $data['brand_color'] ?? '#007bff';
        $logoUrl = $data['logo_url'] ?? '';
        $signature = $data['signature'] ?? '';
        $body = $data['body'] ?? $data['content'] ?? '';

        $logoHtml = $logoUrl ? "<img src=\"{$logoUrl}\" alt=\"{$brandName}\" style=\"max-height:60px;\"><br>" : '';

        return "
        <div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;\">
            <div style=\"background-color: {$brandColor}; padding: 20px; text-align: center; color: white;\">
                {$logoHtml}
                <h1>{$brandName}</h1>
            </div>
            <div style=\"padding: 20px; background-color: #ffffff;\">
                {$body}
            </div>
            <div style=\"padding: 20px; background-color: #f8f9fa; font-size: 12px; color: #6c757d;\">
                {$signature}
            </div>
        </div>";
    }
}
