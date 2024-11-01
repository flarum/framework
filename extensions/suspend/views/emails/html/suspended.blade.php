<x-mail::html.information>
    <x-slot:body>
        {!! $formatter->convert($translator->trans('flarum-suspend.email.suspended.html.body', [
            '{forumTitle}' => $settings->get('forum_title')
        ])) !!}
    </x-slot:body>

    <x-slot:preview>
        {!! $formatter->convert($blueprint->user->suspend_message ?? $translator->trans('flarum-suspend.email.no_reason_given')) !!}
    </x-slot:preview>
</x-mail::html.information>
