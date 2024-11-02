@php
/** @var \Flarum\Messages\Notification\MessageReceivedBlueprint $blueprint */
@endphp

<x-mail::html.notification>
    <x-slot:body>
        {!! $formatter->convert($translator->trans('flarum-messages.email.message_received.html.body', [
            '{user_display_name}' => $blueprint->message->user->display_name,
            '{url}' => $url->to('forum')->route('messages.dialog', ['id' => $blueprint->message->dialog_id, 'near' => $blueprint->message->id])
        ])) !!}
    </x-slot:body>

    <x-slot:preview>
        {!! $blueprint->message->formatContent() !!}
    </x-slot:preview>
</x-mail::html.notification>
