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
    <h1>{{ $translator->trans('core.views.reset.title') }}</h1>

    <form class="form-horizontal" role="form" method="POST" action="{{ app('Flarum\Forum\UrlGenerator')->toRoute('savePassword') }}">
      <input type="hidden" name="csrfToken" value="{{ $csrfToken }}">
      <input type="hidden" name="passwordToken" value="{{ $passwordToken }}">

      <div class="form-group">
        <label class="control-label">{{ $translator->trans('core.views.reset.password_label') }}</label>
        <input type="password" class="form-control" name="password">
      </div>

      <div class="form-group">
        <label class="control-label">{{ $translator->trans('core.views.reset.confirm_password_label') }}</label>
        <input type="password" class="form-control" name="password_confirmation">
      </div>

      <div class="form-group">
        <button type="submit" class="btn btn-primary">{{ $translator->trans('core.views.reset.submit_button') }}</button>
      </div>
    </form>
  </body>
</html>
