<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $translator->trans('flarum-oauth-provider.forum.authorize_error.title') }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f6f7f9; margin: 0; padding: 2rem; }
        .card { max-width: 480px; margin: 3rem auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 2rem; }
        h1 { font-size: 1.25rem; margin: 0 0 0.5rem; }
        .muted { color: #6b7280; font-size: 0.9rem; }
        .badge { display: inline-block; margin-left: 0.25rem; padding: 0 0.4rem; border-radius: 3px; background: #fef3c7; color: #92400e; font-size: 0.8rem; }
        code { font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="card">
    <h1>{{ $translator->trans('flarum-oauth-provider.forum.authorize_error.title') }}</h1>
    <p>
        @if ($clientName !== null)
            {{ $translator->trans('flarum-oauth-provider.forum.authorize_error.intro', ['client' => $clientName]) }}
        @else
            {{ $translator->trans('flarum-oauth-provider.forum.authorize_error.intro_unknown') }}
        @endif
        @if ($unverified)
            <span class="badge">{{ $translator->trans('flarum-oauth-provider.forum.consent.unverified') }}</span>
        @endif
    </p>
    @if ($redirectHost)
        <p class="muted">
            {{ $translator->trans('flarum-oauth-provider.forum.authorize_error.not_redirected', ['host' => $redirectHost]) }}
        </p>
    @endif
    <p class="muted">
        {{ $translator->trans('flarum-oauth-provider.forum.authorize_error.error_code') }} <code>{{ $error }}</code>
    </p>
</div>
</body>
</html>
