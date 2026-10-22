<x-mail::message>
# You've Been Invited!

**{{ $inviterName }}** has invited you to join **{{ $teamName }}** on DigitalMarketingSaaS.

@if($teamDescription)
> {{ $teamDescription }}
@endif

---

Accept the invitation to start collaborating with your team.

<x-mail::button :url="$acceptUrl">
Accept Invitation
</x-mail::button>

<p class="text-sm text-gray-500">
    If you don't have an account yet, you'll be able to create one when you accept this invitation.
</p>

<p class="text-sm text-gray-500">
    This invitation will expire in 7 days.
</p>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
