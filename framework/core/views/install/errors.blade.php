<h2>Hold Up!</h2>

<p>These errors must be resolved before you can continue the installation.</p>

<div class="Errors">
  @foreach ($errors as $error)
    <div class="Error">
      <h3 class="Error-message">{{ $error['message'] }}</h3>
      <p class="Error-detail">{{ $error['detail'] }}</p>
    </div>
  @endforeach
</div>
