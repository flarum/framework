@extends('flarum.forum::layouts.basic')

@section('content')
  <p>
    {{ $translator->trans('core.views.error.404_message') }}
  </p>
  <p>
    <a href="{{ $app->url() }}">
      {{ $translator->trans('core.views.error.404_return_link', ['{forum}' => $settings->get('forum_title')]) }}
    </a>
  </p>
@endsection
