@extends('flarum.forum::layouts.basic')

@section('title', $translator->trans('core.views.error.not_found_title'))

@section('head')
  <meta name="robots" content="noindex">
@endsection

@section('styles')
  .not-found {
    max-width: 640px;
    margin: 0 auto;
    padding: 0 8px;
  }
  .container:has(.not-found) {
    max-width: 700px;
    margin: clamp(48px, 12vh, 120px) auto;
  }
  .not-found h1 {
    font-size: clamp(32px, 6vw, 44px);
    line-height: 1.2;
    letter-spacing: -.03em;
    margin: 0 0 20px;
  }
  .not-found p {
    margin: 0 auto 32px;
    max-width: 480px;
  }
  .not-found img {
    max-width: 100%;
    height: auto;
    border-radius: 12px;
    margin-bottom: 32px;
  }
  .not-found-search {
    text-align: start;
    max-width: 480px;
    margin: 0 auto 32px;
  }
  .not-found-search label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
  }
  .not-found-search-controls {
    display: flex;
    gap: 8px;
  }
  .not-found-search input {
    min-width: 0;
    text-align: start;
    flex: 1;
  }
  .not-found .button {
    white-space: normal;
    padding: 12px 20px;
  }
  .not-found-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 16px 24px;
  }
  @media (max-width: 400px) {
    .not-found-search-controls {
      flex-direction: column;
    }
  }
@endsection

@section('content')
  <main class="not-found">
    @include('flarum.forum::error.not_found.hero')

    <h1>{{ $translator->trans('core.views.error.not_found_title') }}</h1>
    <p>{{ $translator->trans('core.views.error.not_found_description') }}</p>

    @if ($canSearch)
      <form class="not-found-search" role="search" method="get" action="{{ $searchUrl }}">
        <label for="not-found-query">{{ $translator->trans('core.views.error.not_found_search_label') }}</label>
        <div class="not-found-search-controls">
          <input class="form-control" id="not-found-query" type="search" name="q" placeholder="{{ $translator->trans('core.views.error.not_found_search_placeholder') }}" required>
          <button class="button" type="submit">{{ $translator->trans('core.views.error.not_found_search_button') }}</button>
        </div>
      </form>
    @endif

    <nav class="not-found-actions" aria-label="{{ $translator->trans('core.views.error.not_found_actions_label') }}">
      @include('flarum.forum::error.not_found.actions')
    </nav>
  </main>
@endsection
