@extends('flarum.forum::email.html.notification.base')

@section('notificationContent')
{!! $html->render($translator->trans('flarum-mentions.email.user_mentioned.html.body', [
'{mentioner_display_name}' => $blueprint->post->user->display_name,
'{title}' => $blueprint->post->discussion->title,
'{url}' => $url->to('forum')->route('discussion', ['id' => $blueprint->post->discussion_id, 'near' => $blueprint->post->number])
])) !!}
@endsection

@section('contentPreview')
    {!! $html->render($blueprint->post->content) !!}
@endsection
