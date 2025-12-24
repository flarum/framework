<x-mail::plain :greeting="$greeting ?? null" :signoff="$signoff ?? null">
<x-slot:header>
{{ $title ?? $translator->trans('core.email.notification.default_title') }}
</x-slot:header>

<x-slot:content>
{!! $body ?? $slot ?? '' !!}
</x-slot:content>

<x-slot:footer>
{!! $translator->trans('core.email.notification.footer.main_text_plain', ['email' => $user->email, 'type' => $type, 'forumTitle' => $forumTitle]) !!}

{!! $translator->trans('core.email.notification.footer.unsubscribe_text_plain', ['unsubscribeLink' => $unsubscribeLink]) !!}

{!! $translator->trans('core.email.notification.footer.settings_text_plain', ['settingsLink' => $settingsLink]) !!}
</x-slot:footer>
</x-mail::plain>
