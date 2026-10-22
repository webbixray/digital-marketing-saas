<x-mail::message>
# New Contact Form Submission

**From:** {{ $firstName }} {{ $lastName }}  
**Email:** {{ $email }}  
**Subject:** {{ $subject }}

---

**Message:**

{{ $message }}

---

<x-mail::button :url="'mailto:' . $email">
Reply to {{ $firstName }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
