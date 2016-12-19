<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $translator->trans('core.views.reset.title') }}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1">

    <style>
      * {
        outline: 0
      }

      h1 {
        font-weight: 100;
        font-size: 30px;
        margin-top: 50px;
        text-align: center
      }

      body {
        background: #f9f9f9;
        color: #667a99;
        font-family: "Open Sans", Helvetica, Arial, sans-serif;
        margin: 0
      }

      .form-horizontal {
        background: #fff;
        max-width: 310px;
        margin: 0 auto 30px;
        clear: both;
        padding: 20px 20px 30px;
        font-weight: 300;
        line-height: 25px;
        box-shadow: 0 1px 1px #ccc;
        border-radius: 2px;
        color: #666
      }

      .form-control {
        display: block;
        width: 96%;
        height: 25px;
        margin: 5px 0;
        padding: 5px;
        font-size: 14px;
        line-height: 1.42857143;
        color: #555;
        background-color: #fff;
        background-image: none;
        border: 1px solid #ccc;
        outline: 0;
        box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075)
      }

      .form-control:focus {
        border-color: #667a99
      }

      .btn {
        font-weight: normal;
        font-size: 14px;
        border: 1px solid #eee;
        color: #333;
        display: inline-block;
        margin: 2px auto;
        padding: 6px 10px;
        text-decoration: none;
        border-radius: 2px;
        text-align: center;
        white-space: nowrap;
        cursor: pointer;
        background-image: none;
        vertical-align: middle;
        touch-action: manipulation;
        line-height: 1.42857
      }

      .btn:hover {
        text-decoration: none
      }

      .btn-primary {
        background: #667a99;
        border-color: #667a99;
        color: #fff!important
      }

      .btn-primary:hover {
        background: #536582
      }

      .form-group > .btn-primary {
        display: block;
        margin-top: 10px;
        width: 100%
      }
    </style>
  </head>

  <body>
    <h1>{{ $translator->trans('core.views.reset.title') }}</h1>

    @if (! empty($error))
      <div class="form-horizontal error">
        <p class="red">{{ $error }}</p>
      </div>
    @endif

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
