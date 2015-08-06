@if (! $noJs) <noscript> @endif
    <div class="container">
        <div class="Alert">You're viewing the HTML-only version of {{ $forum->attributes->title }}. Upgrade your browser for the full version.</div>
    </div>
    {!! $content !!}
@if (! $noJs) </noscript> @endif
