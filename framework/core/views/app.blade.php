<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title }}</title>
    <meta name="description" content="">
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
        @foreach ($scripts as $file)
          <script src="{{ $forum->attributes->baseUrl . str_replace(public_path(), '', $file) }}"></script>
        @endforeach

        <script>
          try {
            var app = System.get('flarum/app').default;

            babelHelpers._extends(app, {!! json_encode($app) !!});

            @foreach ($bootstrappers as $bootstrapper)
              System.get('{{ $bootstrapper }}');
            @endforeach

            app.boot();
          } catch (e) {
            @if (! $forum->attributes->debug)
                window.location = window.location + '?nojs=1';
            @endif
            throw e;
          }
        </script>
    @endif

    {!! $foot !!}
  </body>
</html>
