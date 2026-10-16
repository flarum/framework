import app from 'flarum/admin/app';

/**
 * Where this forum's API lives.
 *
 * 🚨 A function, with a fallback, because `app.forum.attribute('apiUrl')` — the
 * expression core's own admin components use — throws a TypeError if `app.forum`
 * is not populated. Every request in this extension was built on that
 * expression, and every one of them ran inside a component's `oninit`. A throw
 * there escapes the lifecycle hook, so the page never finishes initialising and
 * whatever was on screen stays there: a loading spinner, forever, with nothing
 * in the UI to say why.
 *
 * The fallback is not a guess. Flarum serves its API at /api on the same origin
 * unless a forum is deliberately split across hosts, so a site where `app.forum`
 * is momentarily absent still works rather than silently doing nothing.
 */
export default function apiUrl(): string {
  try {
    const url = (app as any)?.forum?.attribute?.('apiUrl');

    if (typeof url === 'string' && url !== '') {
      return url;
    }
  } catch {
    // Fall through to the default below.
  }

  return (app as any)?.data?.apiUrl || '/api';
}
