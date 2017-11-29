@extends('flarum.forum::layouts.basic')
@inject('url', 'Flarum\Forum\UrlGenerator')

@section('title', $translator->trans('core.views.log_out.title'))

@section('content')
  <p>{{ $translator->trans('core.views.log_out.log_out_confirmation', ['{forum}' => $forumTitle]) }}</p>

  <p>
    <a href="{{ $url->toRoute('logout') }}?token={{ $csrfToken }}" class="button">
      {{ $translator->trans('core.views.log_out.log_out_button') }}
    </a>
  </p>
@endsection
