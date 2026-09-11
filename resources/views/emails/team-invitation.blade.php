<x-mail::message>
# You've Been Invited!

**{{ $inviterName }}** has invited you to join **{{ $agencyName }}** on DigitalMarketingSaaS as a **{{ $role }}**.

---

Accept the invitation to start collaborating with your team.

<x-mail::button :url="$inviteUrl">
Accept Invitation
</x-mail::button>

If you don't have an account yet, you'll be able to create one when you accept this invitation.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
