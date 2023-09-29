@extends('flarum.forum::email.plain.information.base')

@section('content')
{!! $translator->trans('flarum-suspend.email.unsuspended.plain.body', [
'{forum_url}' => $url->to('forum')->base(),
]) !!}
@endsection
