import app from 'flarum/forum/app';

// Same-origin guard: only trust return URLs on this forum.
const isSafeReturnTo = (url: string | null): url is string => {
  if (!url) return false;
  try {
    const parsed = new URL(url, window.location.origin);
    return parsed.origin === window.location.origin;
  } catch {
    return false;
  }
};

// app.session isn't built yet inside initializers; read the raw payload instead.
const isLoggedIn = (): boolean => {
  const sessionData = (app as unknown as { data?: { session?: { userId?: number } } }).data?.session;
  return !!sessionData?.userId;
};

app.initializers.add('flarum-oauth-provider', () => {
  const params = new URLSearchParams(window.location.search);

  if (params.get('oauth_login') === '1') {
    const returnTo = params.get('return_to');

    if (isSafeReturnTo(returnTo)) {
      try {
        sessionStorage.setItem('flarum-oauth-provider.return_to', returnTo);
      } catch {
        // Storage unavailable; the resume block below will no-op.
      }
    }

    // Strip the query params so a refresh doesn't keep re-triggering this flow.
    try {
      const clean = window.location.pathname + window.location.hash;
      window.history.replaceState(null, '', clean);
    } catch {
      // ignore
    }

    // Only show the login modal if we really are a guest. If the session payload
    // shows a user, the resume block below will redirect on this same boot.
    if (!isLoggedIn()) {
      app.beforeMount(() => {
        setTimeout(() => {
          app.modal.show(() => import('flarum/forum/components/LogInModal'));
        }, 0);
      });
    }
  }

  // Resume: after a successful login reload (or any subsequent page load while
  // a return URL is stashed), bounce back to /oauth/authorize.
  let stashed: string | null = null;
  try {
    stashed = sessionStorage.getItem('flarum-oauth-provider.return_to');
  } catch {
    return;
  }

  if (!stashed || !isLoggedIn() || !isSafeReturnTo(stashed)) return;

  try {
    sessionStorage.removeItem('flarum-oauth-provider.return_to');
  } catch {
    // ignore
  }

  window.location.replace(stashed);
});
