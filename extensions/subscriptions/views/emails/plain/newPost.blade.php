@extends('flarum.forum::email.plain.notification.base')

@section('content')
{!! $translator->trans('flarum-subscriptions.email.new_post.plain.body', [
'{poster_display_name}' => $blueprint->post->user->display_name,
'{title}' => $blueprint->post->discussion->title,
'{url}' => $url->to('forum')->route('discussion', ['id' => $blueprint->post->discussion_id, 'near' => $blueprint->post->number]),
'{content}' => $blueprint->post->content
]) !!}
@endsection
