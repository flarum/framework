@if (! $noJs) <noscript> @endif
<div class="Alert">
    <div class="container">
        @if ($noJs)
            Something went wrong while trying to load the full version of this site.
        @else
            This site is best viewed in a modern browser with JavaScript enabled.
        @endif
    </div>
</div>

{!! $content !!}
@if (! $noJs) </noscript> @endif
