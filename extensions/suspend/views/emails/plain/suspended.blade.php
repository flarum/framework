<x-mail::plain.information>
<x-slot:body>
{!! $translator->trans('flarum-suspend.email.suspended.plain.body', [
'{suspension_message}' => $blueprint->user->suspend_message ?? $translator->trans('flarum-suspend.email.no_reason_given'),
]) !!}
</x-slot:body>
</x-mail::plain.information>
