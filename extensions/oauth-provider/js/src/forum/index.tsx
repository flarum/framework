import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import FieldSet from 'flarum/common/components/FieldSet';
import Session from 'flarum/common/Session';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

import ConnectedApplications from './components/ConnectedApplications';

export { default as extend } from './extend';

const RETURN_TO = 'flarum-oauth-provider.return_to';
// Set while a signed-in user is asked to sign in again, and when they do.
const REAUTH_PENDING = 'flarum-oauth-provider.reauth_pending';
const SIGNED_IN = 'flarum-oauth-provider.signed_in';

const storage = {
  get: (key: string): string | null => {
    try {
      return sessionStorage.getItem(key);
    } catch {
      return null;
    }
  },
  set: (key: string, value: string) => {
    try {
      sessionStorage.setItem(key, value);
    } catch {
      // Storage unavailable; the resume block below will no-op.
    }
  },
  remove: (...keys: string[]) => {
    try {
      keys.forEach((key) => sessionStorage.removeItem(key));
    } catch {
      // ignore
    }
  },
};

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
  extend(Session.prototype, 'login', function (promise: Promise<unknown>) {
    promise.then(() => storage.set(SIGNED_IN, '1'));
  });

  extend('flarum/forum/components/SettingsPage', 'settingsItems', function (items: ItemList<Mithril.Children>) {
    items.add(
      'oauthConnectedApplications',
      <FieldSet
        className="Settings-connectedApplications FieldSet--col"
        label={app.translator.trans('flarum-oauth-provider.forum.connected_apps.heading')}
      >
        <ConnectedApplications />
      </FieldSet>,
      5
    );
  });

  const params = new URLSearchParams(window.location.search);

  // Whether this page load asks the user to sign in; the resume below waits for
  // the reload that follows a successful login.
  let askingToSignIn = false;

  if (params.get('oauth_login') === '1') {
    const returnTo = params.get('return_to');

    if (isSafeReturnTo(returnTo)) {
      storage.set(RETURN_TO, returnTo);
    }

    // Strip the query params so a refresh doesn't keep re-triggering this flow.
    try {
      const clean = window.location.pathname + window.location.hash;
      window.history.replaceState(null, '', clean);
    } catch {
      // ignore
    }

    // A guest signs in. A signed-in user is only asked again when the client
    // required re-authentication (prompt=login, max_age); otherwise the resume
    // block below redirects on this same boot.
    if (!isLoggedIn() || params.get('oauth_reauth') === '1') {
      askingToSignIn = true;

      if (isLoggedIn()) {
        storage.set(REAUTH_PENDING, '1');
        storage.remove(SIGNED_IN);
      }

      app.beforeMount(() => {
        setTimeout(() => {
          app.modal.show(() => import('flarum/forum/components/LogInModal'));
        }, 0);
      });
    }
  }

  // Resume: after a successful login reload (or any subsequent page load while
  // a return URL is stashed), bounce back to /oauth/authorize.
  const stashed = storage.get(RETURN_TO);

  if (askingToSignIn || !stashed || !isLoggedIn() || !isSafeReturnTo(stashed)) return;

  // A signed-in user asked to sign in again who closed the form instead has
  // abandoned the authorization: don't send them back on every page load.
  if (storage.get(REAUTH_PENDING) === '1' && storage.get(SIGNED_IN) !== '1') {
    storage.remove(RETURN_TO, REAUTH_PENDING);
    return;
  }

  storage.remove(RETURN_TO, REAUTH_PENDING, SIGNED_IN);

  window.location.replace(stashed);
});
