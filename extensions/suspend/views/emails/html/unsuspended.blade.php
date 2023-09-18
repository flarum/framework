@extends('flarum.forum::email.html.information.base')

@section('content')
{!! $formatter->convert($translator->trans('flarum-suspend.email.unsuspended.html.body', [
'{recipient_display_name}' => $user->display_name,
'{forumTitle}' => $settings->get('forum_title'),
'{forum_url}' => $url->to('forum')->base(),
])) !!}
@endsection
