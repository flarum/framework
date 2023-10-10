@extends('flarum.forum::email.html.base')

@section('header')
    <h2>{{ $title ?? $translator->trans('core.email.notification.default_title') }}</h2>
@endsection

@section('content')
    @yield('notificationContent')
    @hasSection('contentPreview')
        <div class="content-preview">
            @yield('contentPreview')
        </div>
    @endif
@endsection

@section('footer')
    <p>{!! $formatter->convert($translator->trans('core.email.notification.footer.main_text', ['email' => $user->email, 'type' => $type, 'forumUrl' => $url->to('forum')->base(), 'forumTitle' => $settings->get('forum_title')])) !!}</p>
    <p>{!! $formatter->convert($translator->trans('core.email.notification.footer.unsubscribe_text', ['unsubscribeLink' => $unsubscribeLink])) !!}</p>
    <p>{!! $formatter->convert($translator->trans('core.email.notification.footer.settings_text', ['settingsLink' => $settingsLink])) !!}</p>
@endsection
