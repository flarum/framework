@extends('flarum.forum::layouts.basic')

@section('title', $translator->trans('flarum-oauth-provider.forum.consent.title'))

@section('head')
  <meta name="robots" content="noindex">
@endsection

@section('styles')
  .oauth-consent {
    overflow-wrap: anywhere;
  }
  .oauth-consent h1 {
    font-size: 28px;
    line-height: 1.2;
    margin: 0 0 8px;
  }
  .oauth-consent p {
    margin: 0 0 16px;
  }
  .oauth-consent-muted {
    font-size: 16px;
    color: color-mix(in srgb, var(--text-color) 70%, transparent);
  }
  .oauth-consent-badge {
    display: inline-block;
    padding: 0 8px;
    border-radius: 4px;
    font-size: 14px;
    color: var(--text-color);
    background: rgba(245, 158, 11, .2);
    border: 1px solid rgba(245, 158, 11, .5);
  }
  .oauth-consent-scopes {
    text-align: start;
    margin: 24px 0;
    padding: 16px 20px;
    background: var(--control-bg);
    border: 2px solid var(--control-border-color);
    border-radius: 4px;
  }
  .oauth-consent-scopes p {
    margin: 0 0 8px;
    font-weight: bold;
  }
  .oauth-consent-scopes ul {
    margin: 0;
    padding-inline-start: 20px;
  }
  .oauth-consent-scopes li + li {
    margin-top: 4px;
  }
  .oauth-consent-actions {
    display: flex;
    gap: 8px;
    margin-top: 24px;
  }
  .oauth-consent-actions .button {
    flex: 1;
    white-space: normal;
  }
  .oauth-consent-actions .button-secondary {
    color: var(--text-color);
    background: var(--control-bg);
    box-shadow: inset 0 0 0 2px var(--control-border-color);
  }
  @media (max-width: 400px) {
    .oauth-consent-actions {
      flex-direction: column;
    }
  }
@endsection

@section('content')
  <main class="oauth-consent">
    <h1>{{ $client->getName() }}</h1>

    @if ($unverified)
      <p class="oauth-consent-muted">
        @if ($sourceHost)
          {{ $translator->trans('flarum-oauth-provider.forum.consent.from_host', ['host' => $sourceHost]) }}
        @endif
        <span class="oauth-consent-badge">{{ $translator->trans('flarum-oauth-provider.forum.consent.unverified') }}</span>
      </p>
    @endif

    <p>
      {{ $translator->trans('flarum-oauth-provider.forum.consent.intro', ['username' => $actor->display_name, 'client' => $client->getName()]) }}
    </p>

    @if (count($scopes) > 0)
      <div class="oauth-consent-scopes">
        <p>{{ $translator->trans('flarum-oauth-provider.forum.consent.scopes_heading') }}</p>
        <ul>
          @foreach ($scopes as $id => $description)
            <li>{{ $description }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if ($unverified && $redirectHost)
      <p class="oauth-consent-muted">
        {{ $translator->trans('flarum-oauth-provider.forum.consent.redirect_host', ['host' => $redirectHost]) }}
      </p>
    @endif

    <form method="POST" action="{{ $formAction }}">
      @if ($csrfToken)
        <input type="hidden" name="csrfToken" value="{{ $csrfToken }}">
      @endif
      @foreach ($queryParams as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
      @endforeach

      <div class="oauth-consent-actions">
        <button type="submit" name="oauth_consent_approved" value="1" class="button">
          {{ $translator->trans('flarum-oauth-provider.forum.consent.approve_button') }}
        </button>
        <button type="submit" name="oauth_consent_approved" value="0" class="button button-secondary">
          {{ $translator->trans('flarum-oauth-provider.forum.consent.deny_button') }}
        </button>
      </div>
    </form>
  </main>
@endsection
