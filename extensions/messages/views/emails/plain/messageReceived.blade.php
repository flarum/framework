@php
    /** @var \Flarum\Messages\Notification\MessageReceivedBlueprint $blueprint */
@endphp

<x-mail::plain.notification>
<x-slot:body>
{!! $translator->trans('flarum-messages.email.message_received.plain.body', [
'{user_display_name}' => $blueprint->message->user->display_name,
'{url}' => $url->to('forum')->route('messages.dialog', ['id' => $blueprint->message->dialog_id, 'near' => $blueprint->message->id]),
'{content}' => $blueprint->message->content
]) !!}
</x-slot:body>
</x-mail::plain.notification>
