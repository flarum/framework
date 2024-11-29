<x-mail::html.information>
    <x-slot:body>
        {!! $formatter->convert($translator->trans('flarum-suspend.email.unsuspended.html.body', [
            '{forumTitle}' => $settings->get('forum_title'),
            '{forum_url}' => $url->to('forum')->base(),
        ])) !!}
    </x-slot:body>
</x-mail::html.information>
