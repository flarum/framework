@extends('flarum.forum::layouts.basic')

@section('title', $translator->trans('core.views.unsubscribe_email_error.title'))

@section('content')
    <h2>{{ $translator->trans('core.views.unsubscribe_email_error.title') }}</h2>
        <p>{!! $message !!}</p>

        <a href="{{ $url->to('forum')->base() }}">
            {{ $translator->trans('core.views.unsubscribe_email.return_to_forum', ['forumTitle' => $settings->get('forum_title')]) }}
        </a>
@endsection
