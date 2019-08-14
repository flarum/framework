@extends('flarum.forum::layouts.basic')

@section('content')
  <p>
    {{ $message }}
  </p>
  <p>
    <a href="{{ $app->url() }}">
      {{ $translator->trans('core.views.error.not_found_return_link', ['{forum}' => $settings->get('forum_title')]) }}
    </a>
  </p>
@endsection
