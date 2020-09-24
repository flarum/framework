@extends('flarum.forum::layouts.basic')

@section('content')
  <p>
    {{ $message }}
  </p>
  <p>
    <a href="{{ $url->to('forum')->base() }}">
      {{ $translator->trans('core.views.error.not_found_return_link', ['{forum}' => $settings->get('forum_title')]) }}
    </a>
  </p>
@endsection
