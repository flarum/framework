<x-mail::plain.information>
<x-slot:body>
{!! $translator->trans("flarum-gdpr.email.erasure_completed.{$mode}.body", [
    "{display_name}" => $username
]) !!}
</x-slot:body>
</x-mail::plain.information>
