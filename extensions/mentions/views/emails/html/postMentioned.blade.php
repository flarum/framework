@extends('flarum.forum::email.html.notification.base')

@section('notificationContent')
{!! $formatter->convert($translator->trans('flarum-mentions.email.post_mentioned.html.body', [
'{replier_display_name}' => $blueprint->reply->user->display_name,
'{post_number}' => $blueprint->post->number,
'{title}' => $blueprint->post->discussion->title,
'{url}' => $url->to('forum')->route('discussion', ['id' => $blueprint->reply->discussion_id, 'near' => $blueprint->reply->number])
])) !!}
@endsection

@section('contentPreview')
    {!! $blueprint->reply->formatContent() !!}
@endsection
