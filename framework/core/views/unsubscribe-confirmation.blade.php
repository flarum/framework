@extends('flarum.forum::layouts.basic')

@section('title', $translator->trans('core.views.unsubscribe_email.title'))

@section('content')
    <h2>{{ $translator->trans('core.views.unsubscribe_email.title') }}</h2>
        <p>{!! $formatter->convert($message) !!}</p>
        <p>{{ $translator->trans('core.views.unsubscribe_email.immediate_helptext') }}</p>
        
        <form action="{{ $url->to('forum')->route('notifications.unsubscribe.confirm') }}" method="post">
            <input type="hidden" name="userId" value="{{ $userId }}">
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="csrfToken" value="{{ $csrfToken }}">
            <button type="submit" class="button Button Button--primary">
                {{ $translator->trans('core.views.unsubscribe_email.confirm_button') }}
            </button>
        </form>
        <br/>
        <a href="{{ $url->to('forum')->base() }}">
            {{ $translator->trans('core.views.unsubscribe_email.return_to_forum', ['forumTitle' => $settings->get('forum_title')]) }}
        </a>
@endsection
