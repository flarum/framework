@extends('flarum.forum::email.plain.base')

@section('header')
{{ $title ?? $translator->trans('core.email.notification.default_title') }}
@endsection

@section('content')
@yield('notificationContent')
@endsection

@section('footer')
{!! $translator->trans('core.email.notification.footer.main_text_plain', ['email' => $user->email, 'type' => $type, 'forumTitle' => $forumTitle]) !!}

{!! $translator->trans('core.email.notification.footer.unsubscribe_text_plain', ['unsubscribeLink' => $unsubscribeLink]) !!}

{!! $translator->trans('core.email.notification.footer.settings_text_plain', ['settingsLink' => $settingsLink]) !!}
@endsection
