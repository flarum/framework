<x-mail::html.notification>
    <x-slot:body>
        {!! $formatter->convert($translator->trans('flarum-mentions.email.post_mentioned.html.body', [
            '{replier_display_name}' => $blueprint->reply->user->display_name,
            '{post_number}' => $blueprint->post->number,
            '{title}' => $blueprint->post->discussion->title,
            '{url}' => $url->to('forum')->route('discussion', ['id' => $blueprint->reply->discussion_id, 'near' => $blueprint->reply->number])
        ])) !!}
    </x-slot:body>

    <x-slot:preview>
        {!! $blueprint->reply->formatContent() !!}
    </x-slot:preview>
</x-mail::html.notification>
