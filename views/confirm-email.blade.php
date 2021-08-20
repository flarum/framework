@extends('flarum.forum::layouts.basic')

@section('title', $translator->trans('core.views.confirm_email.title'))

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

    <form class="form" method="POST" action="">
        <input type="hidden" name="csrfToken" value="{{ $csrfToken }}" />

        <p>{{ $translator->trans('core.views.confirm_email.text') }}</p>

        <p class="form-group">
            <button type="submit" class="button">{{ $translator->trans('core.views.confirm_email.submit_button') }}</button>
        </p>
    </form>
@endsection
