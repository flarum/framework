@extends('flarum.forum::layouts.basic')

@section('title', $translator->trans('core.views.offline.title'))

@section('content')
  <p><strong>{{ $translator->trans('core.views.offline.title') }}</strong></p>
  <p>{{ $translator->trans('core.views.offline.text', ['forum' => $settings->get('forum_title')]) }}</p>

  <script>
    // Loads the page again once the forum can be reached.
    (function () {
      function retry() {
        if (navigator.onLine === false) return;

        fetch(@json($retryUrl), { cache: 'no-store' }).then(function () {
          location.reload();
        }, function () {});
      }

      addEventListener('online', retry);
      setInterval(retry, 5000);
    })();
  </script>
@endsection
