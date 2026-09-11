@extends('layouts.public')
@section('title', 'Privacy Policy')

@section('content')
<section class="pricing-header">
    <h1 class="fw-bold">Privacy Policy</h1>
    <p class="lead mb-0">Last updated: {{ date('F j, Y') }}</p>
</section>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="pricing-card" style="text-align: left;">
                    <h3>1. Information We Collect</h3>
                    <p>We collect information you provide directly to us, including:</p>
                    <ul>
                        <li>Name, email address, and payment information when you register</li>
                        <li>Social media account credentials (encrypted) for publishing</li>
                        <li>Content you create using our AI tools</li>
                        <li>Usage data and analytics about your interaction with the Service</li>
                    </ul>

                    <h3>2. How We Use Your Information</h3>
                    <p>We use the information we collect to:</p>
                    <ul>
                        <li>Provide, maintain, and improve the Service</li>
                        <li>Process transactions and send billing notifications</li>
                        <li>Send service-related communications</li>
                        <li>Monitor and analyze usage trends</li>
                        <li>Detect, prevent, and address technical issues or fraud</li>
                    </ul>

                    <h3>3. Data Sharing and Disclosure</h3>
                    <p>We do not sell your personal data. We may share information with:</p>
                    <ul>
                        <li>Service providers who assist in operating the Service</li>
                        <li>Law enforcement when required by law</li>
                        <li>Third parties with your explicit consent</li>
                    </ul>

                    <h3>4. Data Retention</h3>
                    <p>We retain your data for as long as your account is active. Upon account deletion, we will delete or anonymize your data within 30 days, except where retention is required by law.</p>

                    <h3>5. Data Security</h3>
                    <p>We implement industry-standard security measures including:</p>
                    <ul>
                        <li>Encryption at rest and in transit (AES-256, TLS 1.3)</li>
                        <li>Regular security audits and penetration testing</li>
                        <li>Access controls and authentication mechanisms</li>
                        <li>Secure data centers with SOC 2 compliance</li>
                    </ul>

                    <h3>6. Your Rights (GDPR/CCPA)</h3>
                    <p>Depending on your location, you may have the right to:</p>
                    <ul>
                        <li>Access your personal data</li>
                        <li>Rectify inaccurate data</li>
                        <li>Request deletion of your data</li>
                        <li>Object to or restrict processing</li>
                        <li>Data portability</li>
                        <li>Withdraw consent at any time</li>
                    </ul>

                    <h3>7. Cookies and Tracking</h3>
                    <p>We use cookies and similar technologies to:</p>
                    <ul>
                        <li>Remember your preferences and settings</li>
                        <li>Understand how you use our Service</li>
                        <li>Improve and personalize your experience</li>
                    </ul>
                    <p>You can control cookies through your browser settings.</p>

                    <h3>8. Third-Party Services</h3>
                    <p>We may use third-party services including:</p>
                    <ul>
                        <li>Stripe for payment processing</li>
                        <li>AI providers (OpenAI, Anthropic, etc.) for content generation</li>
                        <li>Analytics tools for service improvement</li>
                    </ul>
                    <p>These providers have their own privacy policies governing the use of your information.</p>

                    <h3>9. Children's Privacy</h3>
                    <p>Our Service is not intended for individuals under 16 years of age. We do not knowingly collect personal information from children.</p>

                    <h3>10. Changes to This Policy</h3>
                    <p>We may update this Privacy Policy from time to time. We will notify you of any material changes via email or through the Service.</p>

                    <h3>11. Contact Us</h3>
                    <p>For privacy-related inquiries or to exercise your data rights:</p>
                    <p>Email: privacy@digitalmarketingsaas.com<br>
                    Data Protection Officer: dpo@digitalmarketingsaas.com<br>
                    Address: 123 Marketing Street, San Francisco, CA 94105</p>
                </div>
            </div>
        </div>
    </div>
</section>
