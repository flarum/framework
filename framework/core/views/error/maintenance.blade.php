@extends('flarum.forum::layouts.basic')

@section('title', $translator->trans('core.views.error.maintenance_mode_title'))

@section('content')
  <p>
    {{ $translator->trans('core.views.error.maintenance_mode_message') }}
  </p>
  <p>
      <a href="{{ $url->to('forum')->route('maintenance.login') }}">
          {{ $translator->trans('core.views.error.maintenance_mode_link') }}
      </a>
  </p>
@endsection
