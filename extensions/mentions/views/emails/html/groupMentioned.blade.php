@extends('flarum.forum::email.html.notification.base')

@section('notificationContent')
{!! $html->render($translator->trans('flarum-mentions.email.group_mentioned.html.body', [
'{recipient_display_name}' => $user->display_name,
'{mentioner_display_name}' => $blueprint->post->user->display_name,
'{title}' => $blueprint->post->discussion->title,
'{url}' => $url->to('forum')->route('discussion', ['id' => $blueprint->post->discussion_id, 'near' => $blueprint->post->number])
])) !!}
@endsection

@section('contentPreview')
    {!! $html->render($blueprint->post->content) !!}
@endsection
