@extends('flarum.forum::email.html.base')

@section('header')
    <h2>{{ $title ?? $translator->trans('core.email.notification.default_title') }}</h2>
@endsection

@section('content')
    @yield('notificationContent')
    <div class="content-preview">
        @yield('contentPreview')
    </div>
@endsection

@section('footer')
    <p>{!! $translator->trans('core.email.notification.footer.main_text', ['email' => $user->email, 'type' => $type, 'forumTitle' => '<a href="' . $url->to('forum')->base() . '">' . $settings->get('forum_title') . '</a>']) !!}</p>
    <p>{!! $translator->trans('core.email.notification.footer.unsubscribe_text', ['unsubscribeLink' => $unsubscribeLink]) !!}</p>
    <p>{!! $translator->trans('core.email.notification.footer.settings_text', ['settingsLink' => $settingsLink]) !!}</p>
@endsection
