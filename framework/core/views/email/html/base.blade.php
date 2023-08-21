@inject('url', 'Flarum\Http\UrlGenerator')

@php
  $primaryColor = $settings->get('theme_primary_color');
  $secondaryColor = $settings->get('theme_secondary_color');
@endphp

<!DOCTYPE html>
<html lang="{{ $translator->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Flarum Email' }}</title>
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

<div class="header">
    <!-- Email Header -->
    @yield('header')
</div>

<div class="content">
    <!-- Main Email Content -->
    <div class="greeting">
        <p>{!! $translator->trans('core.email.greeting', ['displayName' => $username]) !!}</p>
    </div>
    <div class="main-content">
        @yield('content')
    </div>
</div>

<div class="footer">
    <!-- Email Footer -->
    @yield('footer')
</div>

</body>
</html>
