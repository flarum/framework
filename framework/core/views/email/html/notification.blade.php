<x-mail::html :greeting="$greeting ?? null" :signoff="$signoff ?? null">
    <x-slot:header>
        <h2>{{ $title ?? $translator->trans('core.email.notification.default_title') }}</h2>
    </x-slot:header>

    <x-slot:content>
        {!! $body ?? $slot ?? '' !!}
        @if (isset($preview))
            <div class="content-preview">
                {!! $preview !!}
            </div>
        @endif
    </x-slot:content>

    <x-slot:footer>
        @if ($typeName ?? null)
            <p>{!! $formatter->convert($translator->trans('core.email.notification.footer.main_text', ['email' => $user->email, 'type' => $typeName, 'forumUrl' => $url->to('forum')->base(), 'forumTitle' => $settings->get('forum_title')])) !!}</p>
        @else
            <p>{!! $formatter->convert($translator->trans('core.email.notification.footer.main_text_generic', ['email' => $user->email, 'forumUrl' => $url->to('forum')->base(), 'forumTitle' => $settings->get('forum_title')])) !!}</p>
        @endif
        <p>{!! $formatter->convert($translator->trans('core.email.notification.footer.unsubscribe_text', ['unsubscribeLink' => $unsubscribeLink])) !!}</p>
        <p>{!! $formatter->convert($translator->trans('core.email.notification.footer.settings_text', ['settingsLink' => $settingsLink])) !!}</p>
    </x-slot:footer>
</x-mail::html>
