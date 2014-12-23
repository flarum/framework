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

    <meta name="flarum/config/environment" content="%7B%22modulePrefix%22%3A%22flarum%22%2C%22environment%22%3A%22development%22%2C%22baseURL%22%3A%22/%22%2C%22locationType%22%3A%22hash%22%2C%22EmberENV%22%3A%7B%22FEATURES%22%3A%7B%7D%7D%2C%22APP%22%3A%7B%22LOG_ACTIVE_GENERATION%22%3Atrue%2C%22LOG_VIEW_LOOKUPS%22%3Atrue%7D%2C%22contentSecurityPolicyHeader%22%3A%22Content-Security-Policy-Report-Only%22%2C%22contentSecurityPolicy%22%3A%7B%22default-src%22%3A%22%27none%27%22%2C%22script-src%22%3A%22%27self%27%20%27unsafe-eval%27%22%2C%22font-src%22%3A%22%27self%27%22%2C%22connect-src%22%3A%22%27self%27%22%2C%22img-src%22%3A%22%27self%27%22%2C%22style-src%22%3A%22%27self%27%22%2C%22media-src%22%3A%22%27self%27%22%7D%2C%22exportApplicationGlobal%22%3Atrue%7D" />

    <base href="/">

    {{ app('flarum.web.assetManager')->styles() }}

  </head>
  <body>
    {{ app('flarum.web.assetManager')->scripts() }}
    <script>
      // window.Flarum.registerPlugin(require('flarum/categories')['default']); // todo: make dynamic
    </script>
  </body>
</html>
