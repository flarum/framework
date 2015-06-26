<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title }}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1">

    <!-- Theme color for Android phones -->
    <meta name="theme-color" content="{{ $config['theme_primary_color'] }}">

    @foreach ($styles as $file)
      <link rel="stylesheet" href="{{ str_replace(public_path(), '', $file) }}">
    @endforeach
  </head>

  <body>
    @include($layout)

    <div id="modal"></div>
    <div id="alerts"></div>

    @foreach ($scripts as $file)
      <script src="{{ str_replace(public_path(), '', $file) }}"></script>
    @endforeach
    <script>
      var app = require('flarum/app')['default'];
      app.config = {!! json_encode($config) !!};
      app.preload = {
        data: {!! json_encode($data) !!},
        response: {!! json_encode($response) !!},
        session: {!! json_encode($session) !!}
      };
      app.boot();
    </script>
  </body>
</html>
