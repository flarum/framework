import app from '../app';

const escapeRegExp = (text: string) => text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

/**
 * Patterns compiled so far, keyed by route template (base path included), so
 * each one is built on the first click that needs it rather than on every click.
 */
const patterns = new Map<string, RegExp>();

/**
 * Turn a route template into a pattern for a path. In Mithril's route syntax,
 * `:param` is one path segment and `:param...` is the rest of the path.
 */
function compile(template: string): RegExp {
  let pattern = patterns.get(template);

  if (!pattern) {
    const segments = template
      .replace(/\/+$/, '')
      .split('/')
      .map((segment) => (segment.startsWith(':') ? (segment.endsWith('...') ? '.*' : '[^/]+') : escapeRegExp(segment)));

    pattern = new RegExp(`^${segments.join('/')}/?$`);
    patterns.set(template, pattern);
  }

  return pattern;
}

/**
 * Whether the forum registered a route for this path. `app.routes` is the
 * table Mithril's routes are built from (see `mapRoutes()`), extensions'
 * routes included.
 */
function hasRoute(path: string, basePath: string): boolean {
  return Object.values(app.routes).some((route) => compile(basePath + route.path).test(path));
}

/**
 * Follow links that point back at this forum without reloading the page.
 *
 * A discussion link copied from the address bar and pasted into a post is an
 * ordinary `<a href="https://myforum.com/d/1">`. Left alone, clicking it tears
 * down the whole application and boots it again, which is slow and loses the
 * reader's place — even though the destination is a route this app already
 * knows how to render.
 *
 * The server marks these links while rendering the post, so this only has to
 * recognise the marker rather than work out what is internal on the client.
 *
 * One listener on the document handles every post on the page, including posts
 * loaded later and content rendered by extensions, because the check happens
 * when a click actually arrives rather than when the markup is created.
 */
export default function routeInternalLinks() {
  document.addEventListener('click', (e: MouseEvent) => {
    // Anything but an unmodified left click means the reader has asked for
    // something other than "go there now" — a new tab, a download, a menu —
    // and the browser does all of those better than we would.
    if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.defaultPrevented) return;

    const link = (e.target as HTMLElement | null)?.closest?.('a.UrlLink--internal') as HTMLAnchorElement | null;

    if (!link) return;

    // `target` may have been changed by an extension after the server set it;
    // if the link is meant for another tab, let it go there.
    if (link.target && link.target !== '_self') return;

    const href = link.getAttribute('href');

    if (!href) return;

    let url: URL;

    try {
      url = new URL(href, document.baseURI);
    } catch {
      return;
    }

    // The marker was written when the post was rendered, which may have been
    // before the forum moved. Confirm against the address this page is
    // actually being served from rather than trusting the class alone.
    if (url.origin !== window.location.origin) return;

    const basePath = app.forum.attribute<string>('basePath') || '';
    const path = url.pathname;

    if (basePath && !(path === basePath || path.startsWith(basePath + '/'))) return;

    // The server marks every link to the forum's own host as internal,
    // whatever the path: an uploaded file, the admin panel, the API, a feed,
    // another app on the same host. Mithril has no route for those and falls
    // back to the index, so only a path the forum routes is handled here.
    if (!hasRoute(path, basePath)) return;

    // What Mithril routes on *includes* the base path. The forum registers its
    // routes with the base path already baked into each pattern
    // (`mapRoutes(routes, basePath)`) and leaves `m.route.prefix` empty, so a
    // path with the base path taken off matches no route at all and Mithril
    // quietly falls back to the default one — sending every internal link on a
    // forum installed in a subdirectory to the index instead.
    const route = path + url.search + url.hash;

    // A link to the exact page we are on, anchor and all, has nowhere to go.
    // Letting the browser handle it keeps same-page anchor jumps working.
    if (url.href === window.location.href) return;

    e.preventDefault();

    m.route.set(route);
  });
}
