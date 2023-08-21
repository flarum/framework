@extends('flarum.forum::email.html.base')

@section('header')
    <!-- Specific header for notification emails -->
    <h2>{{ $title ?? $translator->trans('core.email.notification.default_title') }}</h2>
@endsection

@section('content')
    <!-- Content specific to notification emails -->
    @yield('notificationContent')
@endsection

@section('footer')
    <!-- Specific footer for notification emails -->
    <p>{!! $translator->trans('core.email.notification.footer.main_text', ['email' => $user->email, 'type' => $type, 'forumTitle' => '<a href="' . $url->to('forum')->base() . '">' . $settings->get('forum_title') . '</a>']) !!}</p>
    <p>{!! $translator->trans('core.email.notification.footer.unsubscribe_text', ['unsubscribeLink' => $unsubscribeLink]) !!}</p>
    <p>{!! $translator->trans('core.email.notification.footer.settings_text', ['settingsLink' => $settingsLink]) !!}</p>
@endsection
