<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Reset Your Password</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1">
  </head>

  <body>
    <h1>Reset Your Password</h1>

    <form class="form-horizontal" role="form" method="POST" action="{{ app('Flarum\Http\UrlGeneratorInterface')->toRoute('flarum.forum.savePassword') }}">
      <input type="hidden" name="token" value="{{ $token }}">

      <div class="form-group">
        <label class="control-label">Password</label>
        <input type="password" class="form-control" name="password">
      </div>

      <div class="form-group">
        <label class="control-label">Confirm Password</label>
        <input type="password" class="form-control" name="password_confirmation">
      </div>

      <div class="form-group">
        <button type="submit" class="btn btn-primary">Reset Password</button>
      </div>
    </form>
  </body>
</html>
