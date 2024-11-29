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
        .content-preview {
            background-color: #f7f7f7; /* Light gray background */
            padding: 15px;
            margin: 15px 0;
            border: 1px solid #e1e1e1;
            border-radius: 5px;
        }
        .signoff {
            margin-top: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="Header-title">
        <a href="{{ $url->to('forum')->base() }}" id="home-link">
            @if ($settings->get('logo_path'))
                <img src="{{ $url->to('forum')->base() . '/assets/' . $settings->get('logo_path') }}" alt="{{ $settings->get('forum_title') }}" class="Header-logo">
            @else
                {{ $settings->get('forum_title') }}
            @endif
        </a>
    </div>
    {{ $header ?? '' }}
</div>

<div class="content">
    @if(!isset($greeting) || $greeting !== false)
        <div class="greeting">
            <p>{!! $translator->trans('core.email.greeting', ['displayName' => $username]) !!}</p>
        </div>
    @endif
    <div class="main-content">
        {{ $content ?? '' }}
    </div>
    @if(!isset($signoff) || $signoff !== false)
        <div class="signoff">
            <p>{!! $translator->trans('core.email.signoff', ['forumTitle' => $settings->get('forum_title')]) !!}</p>
        </div>
    @endif
</div>

<div class="footer">
    {{ $footer ?? '' }}
</div>

</body>
</html>
