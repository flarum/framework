import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../../src/forum/app';
import labelDiscussionLinks from '../../../../src/forum/utils/labelDiscussionLinks';

/**
 * The composer preview is rendered in the browser and never reaches the
 * server, so the rule deciding which links become `#123` labels exists twice:
 * once in PHP for saved posts, once here for the preview.
 *
 * These tests are the guard on the client half. They deliberately mirror
 * `tests/integration/formatter/InternalLinksTest.php` case for case — if the
 * two ever disagree, the preview lies about what the writer will get.
 */
beforeAll(() => {
  bootstrapForum();
  app.boot();
});

const ORIGIN = 'http://localhost';

function setForum(attributes: Record<string, unknown>) {
  for (const [key, value] of Object.entries(attributes)) {
    app.forum.data.attributes![key] = value as any;
  }
}

beforeEach(() => {
  setForum({ basePath: '', faviconUrl: null });
});

/**
 * Render a link the way the JS formatter would — an anchor whose text is the
 * address, which is what an autolinked bare URL produces.
 */
function preview(href: string, text: string = href): HTMLElement {
  const el = document.createElement('div');
  el.innerHTML = `<p><a href="${href}">${text}</a></p>`;

  labelDiscussionLinks(el);

  return el;
}

describe('labelDiscussionLinks', () => {
  it('shows a discussion link as the discussion it points at', () => {
    const el = preview(`${ORIGIN}/d/123-the-slug`);

    expect(el.querySelector('.UrlLink-discussion')?.textContent).toBe('#123');
    expect(el.textContent).not.toContain('the-slug');
  });

  it('keeps the address in the href so the link still works', () => {
    const el = preview(`${ORIGIN}/d/123-the-slug`);

    expect(el.querySelector('a')?.getAttribute('href')).toBe(`${ORIGIN}/d/123-the-slug`);
  });

  it('says which post when the link points at one', () => {
    const el = preview(`${ORIGIN}/d/123-the-slug/4`);

    expect(el.querySelector('.UrlLink-discussion')?.textContent).toBe('#123');
    expect(el.querySelector('.UrlLink-post')?.textContent).toBe('4');
  });

  it('shows no post number for a link to the discussion itself', () => {
    expect(preview(`${ORIGIN}/d/123-the-slug`).querySelector('.UrlLink-post')).toBeNull();
  });

  it('works for a discussion with no slug', () => {
    expect(preview(`${ORIGIN}/d/123`).querySelector('.UrlLink-discussion')?.textContent).toBe('#123');
  });

  it('carries the favicon when the forum has one', () => {
    setForum({ faviconUrl: 'http://localhost/assets/favicon-abc.png' });

    const favicon = preview(`${ORIGIN}/d/123`).querySelector('img.UrlLink-favicon');

    expect(favicon?.getAttribute('src')).toBe('http://localhost/assets/favicon-abc.png');
    // Decorative: the label beside it already says where the link goes.
    expect(favicon?.getAttribute('alt')).toBe('');
    expect(favicon?.getAttribute('aria-hidden')).toBe('true');
  });

  it('leaves no broken image when the forum has no favicon', () => {
    const el = preview(`${ORIGIN}/d/123`);

    expect(el.querySelector('img')).toBeNull();
    expect(el.querySelector('.UrlLink-discussion')?.textContent).toBe('#123');
  });

  it('leaves a link alone when the writer chose their own words', () => {
    // `[click here](url)` is the writer's text, and replacing it would
    // overwrite what they wrote.
    const el = preview(`${ORIGIN}/d/123-the-slug`, 'click here');

    expect(el.textContent).toBe('click here');
    expect(el.querySelector('.UrlLink-discussion')).toBeNull();
  });

  it('leaves links to other sites alone', () => {
    const el = preview('https://example.com/d/123');

    expect(el.querySelector('.UrlLink-discussion')).toBeNull();
    expect(el.querySelector('a')?.classList.contains('UrlLink--internal')).toBe(false);
  });

  it('does not treat a host that merely starts the same as ours', () => {
    // localhost.evil.com is not localhost, and a prefix match would let an
    // attacker dress their link up as one of the forum's own.
    expect(preview('http://localhost.evil.com/d/123').querySelector('.UrlLink-discussion')).toBeNull();
  });

  it('leaves internal links that are not discussions alone', () => {
    const el = preview(`${ORIGIN}/u/ianm`);

    expect(el.querySelector('.UrlLink-discussion')).toBeNull();
    expect(el.textContent).toBe(`${ORIGIN}/u/ianm`);
  });

  it('leaves a position it cannot label as a plain address', () => {
    // `near-something` is a position, not a post number, and claiming it as
    // one would show a number that was never there.
    const el = preview(`${ORIGIN}/d/123-the-slug/near-something`);

    expect(el.querySelector('.UrlLink-discussion')).toBeNull();
  });

  it('labels discussions when the forum runs under a base path', () => {
    setForum({ basePath: '/forum' });

    expect(preview(`${ORIGIN}/forum/d/123-the-slug/4`).querySelector('.UrlLink-discussion')?.textContent).toBe('#123');
  });

  it('does not claim a path that merely starts like the base path', () => {
    // A forum at /forum does not own /forums.
    setForum({ basePath: '/forum' });

    expect(preview(`${ORIGIN}/forums/d/123`).querySelector('.UrlLink-discussion')).toBeNull();
  });

  it('can be run twice without doubling the label', () => {
    // The preview re-renders as the writer types, so this must not compound
    // its own output.
    const el = document.createElement('div');
    el.innerHTML = `<p><a href="${ORIGIN}/d/123-the-slug/4">${ORIGIN}/d/123-the-slug/4</a></p>`;

    labelDiscussionLinks(el);
    const once = el.innerHTML;

    labelDiscussionLinks(el);

    expect(el.innerHTML).toBe(once);
    expect(el.querySelectorAll('.UrlLink-discussion')).toHaveLength(1);
  });
});
