@extends('flarum.forum::layouts.basic')

@section('title', $translator->trans('flarum-gdpr.views.confirm_erasure.title'))

@section('content')
    <form class="form" method="POST" action="">
        <input type="hidden" name="csrfToken" value="{{ $csrfToken }}" />

        <p>{{ $translator->trans('flarum-gdpr.views.confirm_erasure.text') }}</p>

        <p class="form-group">
            <button type="submit" class="button">{{ $translator->trans('flarum-gdpr.views.confirm_erasure.submit_button') }}</button>
        </p>
    </form>
@endsection
