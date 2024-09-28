@php
    /** @var \Flarum\Messages\Notification\MessageReceivedBlueprint $blueprint */
@endphp

@extends('flarum.forum::email.plain.notification.base')

@section('content')
{!! $translator->trans('flarum-messages.email.message_received.plain.body', [
'{user_display_name}' => $blueprint->message->user->display_name,
'{url}' => $url->to('forum')->route('messages.dialog', ['id' => $blueprint->message->dialog_id, 'near' => $blueprint->message->id]),
'{content}' => $blueprint->message->content
]) !!}
@endsection
