@php
/** @var \Flarum\Messages\Notification\MessageReceivedBlueprint $blueprint */
@endphp

@extends('flarum.forum::email.html.notification.base')

@section('notificationContent')
{!! $formatter->convert($translator->trans('flarum-messages.email.message_received.html.body', [
'{user_display_name}' => $blueprint->message->user->display_name,
'{url}' => $url->to('forum')->route('messages.dialog', ['id' => $blueprint->message->dialog_id, 'near' => $blueprint->message->id])
])) !!}
@endsection

@section('contentPreview')
    {!! $blueprint->message->formatContent() !!}
@endsection
