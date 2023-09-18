@extends('flarum.forum::email.html.notification.base')

@section('notificationContent')
{!! $formatter->convert($translator->trans('flarum-subscriptions.email.new_post.html.body', [
'{poster_display_name}' => $blueprint->post->user->display_name,
'{title}' => $blueprint->post->discussion->title,
'{url}' => $url->to('forum')->route('discussion', ['id' => $blueprint->post->discussion_id, 'near' => $blueprint->post->number])
])) !!}
@endsection

@section('contentPreview')
    {!! $blueprint->post->formatContent() !!}
@endsection
