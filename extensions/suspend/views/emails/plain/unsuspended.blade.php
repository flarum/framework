@extends('flarum.forum::email.plain.information.base')

@section('content')
{!! $translator->trans('flarum-suspend.email.unsuspended.plain.body', [
'{recipient_display_name}' => $user->display_name,
'{forum_url}' => $url->to('forum')->base(),
]) !!}
@endsection
