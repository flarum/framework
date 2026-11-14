<a class="button" href="{{ $url->to('forum')->base() }}">
  {{ $translator->trans('core.views.error.not_found_home_link') }}
</a>
@if ($canSearch)
  <a href="{{ $discussionsUrl }}">
    {{ $translator->trans('core.views.error.not_found_discussions_link') }}
  </a>
@endif
