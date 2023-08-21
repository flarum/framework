@extends('flarum.forum::email.plain.base')

@section('header')
    <!-- Specific header for notification emails -->
    {{ $title ?? 'Notification' }}
@endsection

@section('content')
    <!-- Content specific to notification emails -->
    @yield('notificationContent')
@endsection

@section('footer')
    <!-- Specific footer for notification emails -->
    This email was sent to {{ $user->email }} because you are subcribed to "{{ $type }}" notifications on {{ $forumTitle }}.

    If you'd like to stop receiving this type of notification, unsubscribe here: {{ $unsubscribeLink }}

    Manage your notification settings: {{ $settingsLink }}
@endsection
