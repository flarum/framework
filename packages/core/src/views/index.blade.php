<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title }}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">

    <base href="/">

    {{ app('flarum.web.assetManager')->styles() }}

  </head>
  <body>
    <script>
      window.FlarumENV = {"environment":"development","baseURL":"/","EmberENV":{"FEATURES":{"query-params-new":true}},"APP":{"LOG_MODULE_RESOLVER":true,"LOG_TRANSITIONS":true,"LOG_TRANSITIONS_INTERNAL":true},"LOG_MODULE_RESOLVER":true};
      window.EmberENV = window.FlarumENV.EmberENV;
    </script>
    {{ app('flarum.web.assetManager')->scripts() }}
    <script>
      window.Flarum = require('flarum/app')['default'].create(FlarumENV.APP);
      // window.Flarum.registerPlugin(require('flarum/categories')['default']); // todo: make dynamic
    </script>
  </body>
</html>
