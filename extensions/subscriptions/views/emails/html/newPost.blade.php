<x-mail::html.notification>
    <x-slot:body>
        {!! $formatter->convert($translator->trans('flarum-subscriptions.email.new_post.html.body', [
            '{poster_display_name}' => $blueprint->post->user->display_name,
            '{title}' => $blueprint->post->discussion->title,
            '{url}' => $url->to('forum')->route('discussion', ['id' => $blueprint->post->discussion_id, 'near' => $blueprint->post->number])
        ])) !!}
    </x-slot:body>

    <x-slot:preview>
        {!! $blueprint->post->formatContent() !!}
    </x-slot:preview>
</x-mail::html.notification>
