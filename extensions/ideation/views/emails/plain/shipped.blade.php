<x-mail::plain.notification>
{!! $translator->trans('flarum-ideation.email.shipped.body', ['recipient_display_name' => $user->display_name, 'title' => $blueprint->discussion->title]) !!}

{!! $url->to('forum')->route('discussion', ['id' => $blueprint->discussion->id]) !!}
</x-mail::plain.notification>
