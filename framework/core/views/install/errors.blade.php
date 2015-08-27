<h2>Hold Up!</h2>

<p>These errors must be resolved before you can continue the installation. If you're having trouble, get help on the <a href="http://flarum.org/docs/troubleshooting" target="_blank">Flarum website</a>.</p>

<div class="Errors">
  @foreach ($errors as $error)
    <div class="Error">
      <h3 class="Error-message">{!! $error['message'] !!}</h3>
      @if (! empty($error['detail']))
        <p class="Error-detail">{!! $error['detail'] !!}</p>
      @endif
    </div>
  @endforeach
</div>
