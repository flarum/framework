<x-mail::plain>
<x-slot:header>
{{ $title ?? $translator->trans('core.email.informational.default_title') }}
</x-slot:header>

<x-slot:content>
{{ $slot ?? $body ?? '' }}
</x-slot:content>

<x-slot:footer>
{!! $translator->trans('core.email.informational.footer_plain', ['userEmail' => $userEmail, 'forumTitle' => $forumTitle]) !!}
</x-slot:footer>
</x-mail::plain>
