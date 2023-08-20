@extends('flarum.forum::layouts.basic')

@section('title', $translator->trans('core.views.unsubscribe_email.title'))

@section('content')
    <div class="container">
        <h2>{{ $translator->trans('core.views.unsubscribe_email.title') }}</h2>
        <p>{!! $message !!}</p>

        <a href="{{ $url->to('forum')->base() }}" class="button">
            {{ $translator->trans('core.views.unsubscribe_email.return_to_forum', ['forumTitle' => $settings->get('forum_title')]) }}
        </a>
    </div>
@endsection
