<div id="flarum-loading" style="display: none">
  {{ $translator->trans('core.views.content.loading_text') }}
</div>

@if (! $noJs) <noscript> @endif
<div class="Alert">
  <div class="container">
    @if ($noJs)
      {{ $translator->trans('core.views.content.load_error_message') }}
    @else
      {{ $translator->trans('core.views.content.javascript_disabled_message') }}
    @endif
  </div>
</div>

{!! $content !!}
@if (! $noJs) </noscript> @endif
