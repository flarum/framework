<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title }}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1">
    <meta name="flarum/config/environment" content="{{ rawurlencode(json_encode($config)) }}">
    <base href="/">
    {!! $styles !!}
  </head>
  <body>
    <div id="assets-loading" class="fade">Loading...</div>
    <script>
        setTimeout(function() {
            var loading = document.getElementById('assets-loading');
            if (loading) {
                loading.className += ' in';
            }
        }, 1000);
    </script>

    {!! $content !!}

    <script>
        var FLARUM_DATA = {!! json_encode($data) !!};
        var FLARUM_SESSION = {!! json_encode($session) !!};
        var FLARUM_ALERT = {!! json_encode($alert) !!};
    </script>
    {!! $scripts !!}
  </body>
</html>
