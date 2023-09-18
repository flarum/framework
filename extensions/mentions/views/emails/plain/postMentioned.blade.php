@extends('flarum.forum::email.plain.notification.base')

@section('content')
{!! $translator->trans('flarum-mentions.email.post_mentioned.plain.body', [
'{replier_display_name}' => $blueprint->reply->user->display_name,
'{post_number}' => $blueprint->post->number,
'{title}' => $blueprint->post->discussion->title,
'{url}' => $url->to('forum')->route('discussion', ['id' => $blueprint->reply->discussion_id, 'near' => $blueprint->reply->number]),
'{content}' => $blueprint->reply->content
]) !!}
@endsection
