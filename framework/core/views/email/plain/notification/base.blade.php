@extends('flarum.forum::email.plain.base')

@section('header')
{{ $title ?? $translator->trans('core.email.notification.default_title') }}
@endsection

@section('content')
@yield('notificationContent')
@endsection

@section('footer')
This email was sent to {{ $user->email }} because you are subcribed to "{{ $type }}" notifications on {{ $forumTitle }}.

If you'd like to stop receiving this type of notification, unsubscribe here: {{ $unsubscribeLink }}

Manage your notification settings: {{ $settingsLink }}
@endsection
