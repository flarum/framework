@extends('flarum.forum::layouts.basic')

@section('title', $translator->trans('core.views.offline.title'))

@section('content')
  <p><strong>{{ $translator->trans('core.views.offline.title') }}</strong></p>
  <p>{{ $translator->trans('core.views.offline.text', ['forum' => $settings->get('forum_title')]) }}</p>

  <script>
    (function () {
      var retryUrl = @json($retryUrl);

      // Opened at its own address, the retry would succeed and load this
      // page again, forever: it only retries in place of another page.
      if (location.pathname === retryUrl) return;

      function retry() {
        if (navigator.onLine === false) return;

        fetch(retryUrl, { cache: 'no-store' }).then(function (response) {
          if (response.ok) location.reload();
        }, function () {});
      }

      addEventListener('online', retry);
      setInterval(retry, 5000);
    })();
  </script>
@endsection
