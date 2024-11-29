<x-mail::plain.information>
<x-slot:body>
{!! $translator->trans('flarum-suspend.email.unsuspended.plain.body', [
'{forum_url}' => $url->to('forum')->base(),
]) !!}
</x-slot:body>
</x-mail::plain.information>
