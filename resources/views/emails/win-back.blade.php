<x-mail::message>
# We Miss You, {{ $name }}!

It's been a while since you last visited DigitalMarketingSaaS. We've been working hard to improve our platform with new features and better AI capabilities.

---

## Come Back and Save {{ $discountPercent }}%

Use the code below to get **{{ $discountPercent }}% off** your next subscription:

<div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; margin: 20px 0;">
    <span style="font-size: 24px; font-weight: bold; color: #6366f1;">{{ $discountCode }}</span>
</div>

---

## What's New Since You Left

- **Improved AI Content Generation** — Better quality, more platforms
- **Content Calendar** — Visual planning and scheduling
- **Referral Program** — Earn credits by inviting friends
- **Advanced Analytics** — Deeper insights into your performance

<x-mail::button :url="{{ route('login') }}">
Claim Your Discount
</x-mail::button>

If you have any questions or feedback, simply reply to this email.

Thanks,<br>
The {{ config('app.name') }} Team

---

<small>If you no longer wish to receive these emails, you can [unsubscribe]({{ url('/unsubscribe') }}).</small>
</x-mail::message>
