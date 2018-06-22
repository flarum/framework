<!doctype html>
<html dir="{{ $direction }}" lang="{{ $language }}">
  <head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1">
    <meta name="theme-color" content="{{ array_get($forum, 'attributes.themePrimaryColor') }}">
    @if (! $allowJs)
      <meta name="robots" content="noindex" />
    @endif

    @foreach ($cssUrls as $url)
      <link rel="stylesheet" href="{{ $url }}">
    @endforeach

    @if ($faviconUrl = array_get($forum, 'attributes.faviconUrl'))
      <link href="{{ $faviconUrl }}" rel="shortcut icon">
    @endif

    {!! $head !!}
  </head>

  <body>
    {!! $layout !!}

    <div id="modal"></div>
    <div id="alerts"></div>

    @if ($allowJs)
      <script>
        document.getElementById('flarum-loading').style.display = 'block';
        var flarum = {extensions: {}};
      </script>

      @foreach ($jsUrls as $url)
        <script src="{{ $url }}"></script>
      @endforeach

      <script>
        document.getElementById('flarum-loading').style.display = 'none';
        @if (! $debug)
        try {
        @endif
          flarum.core.app.load(@json($payload));
          flarum.core.app.bootExtensions(flarum.extensions);
          flarum.core.app.boot();
        @if (! $debug)
        } catch (e) {
          window.location += (window.location.search ? '&' : '?') + 'nojs=1';
          throw e;
        }
        @endif
      </script>
    @else
      <script>
        window.history.replaceState(null, null, window.location.toString().replace(/([&?]nojs=1$|nojs=1&)/, ''));
      </script>
    @endif

    {!! $foot !!}
  </body>
</html>
