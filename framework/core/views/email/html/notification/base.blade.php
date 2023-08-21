@extends('flarum.forum::email.html.base')

@section('header')
    <!-- Specific header for notification emails -->
    <h2>{{ $title ?? 'Notification' }}</h2>
@endsection

@section('content')
    <!-- Content specific to notification emails -->
    @yield('notificationContent')
@endsection

@section('footer')
    <!-- Specific footer for notification emails -->
    <p>This email was sent to {{ $user->email }} because you are subcribed to "{{ $type }}" notifications on {{ $forumTitle }}.</p>
    <p>If you'd like to stop receiving this type of notification, <a href="{{ $unsubscribeLink }}">unsubscribe here</a>.</p>
    <p>Manage your notification settings <a href="{{ $settingsLink }}">here</a>.</p>
@endsection
