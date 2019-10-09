@extends('flarum.forum::layouts.basic')

@section('content')
  <p>
    {{ $message }}
  </p>
  <p>
    <form class="form" method="GET" action="/">
        <input type="text" name="q" placeholder="{{ $translator->trans('core.forum.header.search_placeholder') }}"/>
        <button type="submit" class="button">Go</button>
    </form>
  </p>
  <p>
    <a href="{{ $app->url() }}">
      {{ $translator->trans('core.views.error.not_found_return_link', ['{forum}' => $settings->get('forum_title')]) }}
    </a>
  </p>
@endsection
