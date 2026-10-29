@php
  $primaryColor = $settings->get('theme_primary_color', '#000');

  // Follow the forum's color scheme: always dark for a dark one (dark, dark-hc),
  // the visitor's system setting for auto, and light otherwise.
  $darkMedia = match (true) {
    str_starts_with($colorScheme, 'dark') => 'all',
    $colorScheme === 'auto' => '(prefers-color-scheme: dark)',
    default => null,
  };
@endphp

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>@hasSection('title') @yield('title') - @endif{{ $settings->get('forum_title') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if ($faviconUrl)
      <link rel="shortcut icon" href="{{ $faviconUrl }}">
    @endif

    <style>
      :root {
        color-scheme: light;
        --body-bg: #fff;
        --text-color: #333;
        --control-bg: #fff;
        --control-border-color: #eee;
      }
      @if ($darkMedia)
      @media {{ $darkMedia }} {
        :root {
          color-scheme: dark;
          --body-bg: {{ $darkBodyBg }};
          --text-color: #ddd;
          --control-bg: rgba(255, 255, 255, .05);
          --control-border-color: rgba(255, 255, 255, .15);
        }
      }
      @endif
      * {
       box-sizing: border-box;
      }
      body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
        font-size: 18px;
        text-align: center;
        line-height: 1.5;
        color: var(--text-color);
        background: var(--body-bg);
      }
      input,
      button,
      select,
      textarea {
        font-family: inherit;
        font-size: inherit;
        line-height: inherit;
      }
      a {
        cursor: pointer;
        color: {{ $primaryColor }};
        text-decoration: none;
        font-weight: bold;
      }
      a:hover {
        text-decoration: underline;
      }
      .container {
        margin: 100px auto;
        max-width: 450px;
        padding: 0 15px;
      }
      .button {
        display: inline-block;
        padding: 15px 25px;
        background: {{ $primaryColor }};
        color: #fff;
        text-decoration: none;
        text-align: center;
        vertical-align: middle;
        border-radius: 4px;
        cursor: pointer;
        white-space: nowrap;
        font-weight: bold;
        border: 0;
      }
      .button:hover {
        text-decoration: none;
      }
      .button:active,
      .button.active {
        box-shadow: inset 0 3px 5px rgba(0, 0, 0, .125);
      }
      .form {
        max-width: 300px;
        margin: 0 auto;
      }
      .form .button {
        display: block;
        width: 100%;
      }
      .form-control {
        display: block;
        width: 100%;
        text-align: center;
        padding: 15px 20px;
        color: inherit;
        background-color: var(--control-bg);
        border: 2px solid var(--control-border-color);
        border-radius: 4px;
        transition: border-color .15s;
      }
      .form-control:focus,
      .form-control.focus {
         border-color: {{ $primaryColor }};
      }
      .errors {
        color: #d83e3e;
      }
      .errors ul {
        list-style-type: none;
        margin: 0;
        padding: 0;
      }
    </style>
  </head>

  <body>
    <div class="container">
      @yield('content')
    </div>
  </body>
</html>
