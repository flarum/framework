<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $forum->attributes->description }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1">
    <meta name="theme-color" content="{{ $forum->attributes->themePrimaryColor }}">

    @foreach ($styles as $file)
      <link rel="stylesheet" href="{{ $forum->attributes->baseUrl . str_replace(public_path(), '', $file) }}">
    @endforeach

    {!! $head !!}
  </head>

  <body>
    {!! $layout !!}

    <div id="modal"></div>
    <div id="alerts"></div>

    @if (! $noJs)
      <script>
        document.getElementById('flarum-loading').style.display = 'block';
      </script>

      @foreach ($scripts as $file)
        <script src="{{ $forum->attributes->baseUrl . str_replace(public_path(), '', $file) }}"></script>
      @endforeach

      <script>
        document.getElementById('flarum-loading').style.display = 'none';
        @if (! $forum->attributes->debug)
        try {
        @endif
          var app = System.get('flarum/app').default;

          babelHelpers._extends(app, {!! json_encode($app) !!});

          @foreach ($bootstrappers as $bootstrapper)
            System.get('{{ $bootstrapper }}');
          @endforeach

          app.boot();
        @if (! $forum->attributes->debug)
        } catch (e) {
          var nojs = window.location.search ? '&nojs=1' : '?nojs=1';
          window.location = window.location + nojs;
        }
        @endif
      </script>
    @endif

    {!! $foot !!}
  </body>
</html>
