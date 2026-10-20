<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $translator->trans('flarum-oauth-provider.forum.consent.title') }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f6f7f9; margin: 0; padding: 2rem; }
        .card { max-width: 480px; margin: 3rem auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 2rem; }
        h1 { font-size: 1.25rem; margin: 0 0 0.5rem; }
        .muted { color: #6b7280; font-size: 0.9rem; }
        ul { padding-left: 1.2rem; }
        li { margin: 0.25rem 0; }
        .actions { display: flex; gap: 0.5rem; margin-top: 1.5rem; }
        .btn { flex: 1; padding: 0.75rem 1rem; border-radius: 4px; border: 0; cursor: pointer; font-weight: 500; }
        .btn-primary { background: #4863a6; color: #fff; }
        .btn-secondary { background: #e5e7eb; color: #111827; }
    </style>
</head>
<body>
<div class="card">
    <h1>{{ $client->getName() }}</h1>
    <p class="muted">
        {{ $translator->trans('flarum-oauth-provider.forum.consent.intro', ['username' => $actor->display_name, 'client' => $client->getName()]) }}
    </p>

    @if (count($scopes) > 0)
        <p><strong>{{ $translator->trans('flarum-oauth-provider.forum.consent.scopes_heading') }}</strong></p>
        <ul>
            @foreach ($scopes as $id => $description)
                <li>{{ $description }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ $formAction }}">
        @if ($csrfToken)
            <input type="hidden" name="csrfToken" value="{{ $csrfToken }}">
        @endif
        @foreach ($queryParams as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach

        <div class="actions">
            <button type="submit" name="oauth_consent_approved" value="0" class="btn btn-secondary">
                {{ $translator->trans('flarum-oauth-provider.forum.consent.deny_button') }}
            </button>
            <button type="submit" name="oauth_consent_approved" value="1" class="btn btn-primary">
                {{ $translator->trans('flarum-oauth-provider.forum.consent.approve_button') }}
            </button>
        </div>
    </form>
</div>
</body>
</html>
