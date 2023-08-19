{{ $settings->get('forum_title') }} Notification

---------------------------------------------------

@yield('content')

---------------------------------------------------

If you'd like to stop receiving this type of notification, unsubscribe here: {{ $unsubscribeLink }}

Manage your notification settings: {{ $settingsLink }}
