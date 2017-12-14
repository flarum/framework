@extends('flarum.forum::layouts.basic')

@section('title', $translator->trans('core.views.log_out.title'))

@section('content')
  <p>{{ $translator->trans('core.views.log_out.log_out_confirmation', ['{forum}' => $settings->get('forum_title')]) }}</p>

  <p>
    <a href="{{ $url }}" class="button">
      {{ $translator->trans('core.views.log_out.log_out_button') }}
    </a>
  </p>
@endsection
