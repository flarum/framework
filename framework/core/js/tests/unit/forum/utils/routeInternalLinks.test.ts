import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../../src/forum/app';
import routeInternalLinks from '../../../../src/forum/utils/routeInternalLinks';

/**
 * The listener hands a path to Mithril, and Mithril matches it against the
 * routes the forum registered. Those patterns carry the base path
 * (`mapRoutes(routes, basePath)`), so the path handed over has to carry it
 * too — a mismatch is not an error, it is a silent fall back to the default
 * route, which is why a subdirectory install used to send every internal link
 * to the index.
 */
beforeAll(() => {
  bootstrapForum();
  app.boot();

  routeInternalLinks();
});

const ORIGIN = 'http://localhost';

let routed: string | null;

beforeEach(() => {
  routed = null;

  // @ts-expect-error the global mithril stub carries no route helper
  m.route = { set: (path: string) => (routed = path) };

  app.forum.data.attributes!.basePath = '';

  document.body.innerHTML = '';
});

/**
 * Click a link the way the server renders an internal one, and report both
 * where Mithril was asked to go and whether the browser was left to do it.
 */
function click(href: string, init: MouseEventInit = {}): { routed: string | null; prevented: boolean } {
  const link = document.createElement('a');
  link.className = 'UrlLink UrlLink--internal';
  link.href = href;
  link.target = '_self';
  document.body.appendChild(link);

  const event = new MouseEvent('click', { bubbles: true, cancelable: true, button: 0, ...init });
  link.dispatchEvent(event);

  return { routed, prevented: event.defaultPrevented };
}

describe('routeInternalLinks', () => {
  it('follows an internal link without reloading the page', () => {
    expect(click(`${ORIGIN}/d/123-the-slug`)).toEqual({ routed: '/d/123-the-slug', prevented: true });
  });

  it('keeps the base path, which is part of what Mithril matches on', () => {
    app.forum.data.attributes!.basePath = '/forum';

    expect(click(`${ORIGIN}/forum/d/123-the-slug`)).toEqual({ routed: '/forum/d/123-the-slug', prevented: true });
  });

  it('carries the query string and fragment along', () => {
    app.forum.data.attributes!.basePath = '/forum';

    expect(click(`${ORIGIN}/forum/d/123-the-slug?foo=bar#post-4`).routed).toBe('/forum/d/123-the-slug?foo=bar#post-4');
  });

  it('leaves alone an address outside the forum, base path or not', () => {
    app.forum.data.attributes!.basePath = '/forum';

    expect(click(`${ORIGIN}/elsewhere/d/123`)).toEqual({ routed: null, prevented: false });
  });

  it('leaves alone another site', () => {
    expect(click('https://example.com/d/123')).toEqual({ routed: null, prevented: false });
  });

  it('leaves a same-page anchor to the browser', () => {
    expect(click(window.location.href)).toEqual({ routed: null, prevented: false });
  });

  it('lets a modified click open a new tab', () => {
    expect(click(`${ORIGIN}/d/123-the-slug`, { metaKey: true })).toEqual({ routed: null, prevented: false });
  });
});
