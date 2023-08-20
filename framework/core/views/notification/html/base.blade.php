@php
  $primaryColor = $settings->get('theme_primary_color');
  $secondaryColor = $settings->get('theme_secondary_color');
@endphp

<!DOCTYPE html>
<html lang="{{ $translator->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $settings->get('forum_title') }} Notification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            font-size: 14px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #e1e1e1;
            border-radius: 5px;
            background-color: #fff;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>{{ $settings->get('forum_title') }} Notification</h2>
        <hr>
        @yield('content')
        <hr>
        <div class="footer">
            If you'd like to stop receiving this type of notification, <a href="{{ $unsubscribeLink }}">unsubscribe here</a>.<br>
            Manage your notification settings <a href="{{ $settingsLink }}">here</a>.
        </div>
    </div>
</body>
</html>
