@extends('flarum.forum::email.html.notification.base')

@section('notificationContent')
{!! $html->render($translator->trans('flarum-mentions.email.post_mentioned.html.body', [
'{recipient_display_name}' => $user->display_name,
'{replier_display_name}' => $blueprint->reply->user->display_name,
'{post_number}' => $blueprint->post->number,
'{title}' => $blueprint->post->discussion->title,
'{url}' => $url->to('forum')->route('discussion', ['id' => $blueprint->reply->discussion_id, 'near' => $blueprint->reply->number])
])) !!}
@endsection

@section('contentPreview')
    {!! $html->render($blueprint->reply->content) !!}
@endsection
