@extends('flarum.forum::layouts.basic')

@section('content')
  <p>
    {{ $message }}
  </p>
  <p>
    <a href="javascript:history.back()">
      {{ $translator->trans('core.views.error.419_return_link') }}
    </a>
  </p>
@endsection
