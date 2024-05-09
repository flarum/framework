@extends('flarum.forum::layouts.basic')

@section('title', $translator->trans('core.views.log_in.title'))

@section('content')
    @if ($errors->any())
        <div class="errors">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="form" method="POST" action="{{ $url->to('forum')->route('login') }}">
        <input type="hidden" name="csrfToken" value="{{ $csrfToken }}">

        <p class="form-group">
            <input type="text" class="form-control" name="identification" placeholder="{{ $translator->trans('core.views.log_in.username_or_email_placeholder') }}" aria-label="{{ $translator->trans('core.views.log_in.username_or_email_placeholder') }}">
        </p>

        <p class="form-group">
            <input type="password" class="form-control" name="password" autocomplete="current-password" placeholder="{{ $translator->trans('core.views.log_in.password_placeholder') }}" aria-label="{{ $translator->trans('core.views.log_in.password_placeholder') }}">
        </p>

        <p class="form-group">
            <label>
                <input type="checkbox" name="remember" value="1" tabindex="1">
                <span>{{ $translator->trans('core.views.log_in.remember_me_label') }}</span>
            </label>
        </p>

        <p class="form-group">
            <button type="submit" class="button">{{ $translator->trans('core.views.log_in.submit_button') }}</button>
        </p>
    </form>
@endsection
