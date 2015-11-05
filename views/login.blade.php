<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Log In</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1">
  </head>

  <body>
    <h1>Log In</h1>

    <form class="form-horizontal" role="form" method="POST" action="{{ app('Flarum\Admin\UrlGenerator')->toRoute('index') }}">
      <input type="hidden" name="token" value="{{ $token }}">

      <div class="form-group">
        <label class="control-label">Username or Email</label>
        <input type="text" class="form-control" name="identification">
      </div>

      <div class="form-group">
        <label class="control-label">Password</label>
        <input type="password" class="form-control" name="password">
      </div>

      <div class="form-group">
        <button type="submit" class="btn btn-primary">Log In</button>
      </div>
    </form>
  </body>
</html>
