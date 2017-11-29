@extends('flarum.forum::layouts.basic')

@section('content')
  <p>
    {{-- TODO: Change below to @php when Laravel is upgraded --}}
    <?php
      $getTranslationIfExists = function ($code) use ($translator, $settings) {
          $key = 'core.views.error.'.$code.'_message';
          $translation = $translator->trans($key, ['{forum}' => $settings->get('forum_title')]);

          return $translation === $key ? false : $translation;
      };

      if (! $translation = $getTranslationIfExists($error->getCode())) {
          if (! $translation = $getTranslationIfExists(500)) {
              $translation = 'An error occurred while trying to load this page.';
          }
      }
    ?>
    {{ $translation }}
  </p>
@endsection
