<x-mail::html.notification>
    <x-slot:body>
        <p>{{ $translator->trans('flarum-ideation.email.shipped.body', ['recipient_display_name' => $user->display_name, 'title' => $blueprint->discussion->title]) }}</p>
        <p><a href="{{ $url->to('forum')->route('discussion', ['id' => $blueprint->discussion->id]) }}">{{ $translator->trans('flarum-ideation.email.shipped.view_link') }}</a></p>
    </x-slot:body>
</x-mail::html.notification>
