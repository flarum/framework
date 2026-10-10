import $ from 'jquery';
import selectedText from '../../../../src/forum/utils/selectedText';

// jsdom has no innerText. The helper reads it from a detached clone, where it
// is the same as textContent.
beforeAll(() => {
  Object.defineProperty(HTMLElement.prototype, 'innerText', {
    configurable: true,
    get() {
      return this.textContent;
    },
  });
});

function postBody(html: string) {
  document.body.innerHTML = `<div class="Post-body">${html}</div>`;

  return $(document.body).find('.Post-body');
}

function select(range: (range: Range) => void) {
  const selection = window.getSelection()!;
  const created = document.createRange();

  range(created);
  selection.removeAllRanges();
  selection.addRange(created);
}

function quote(html: string): string {
  const $body = postBody(html);

  select((range) => range.selectNodeContents($body[0]));

  return selectedText($body);
}

const URL = 'https://discuss.flarum.org/d/38941-friendsofflarum-move-posts/44';

// What the server renders for a bare link to a discussion on the forum:
// Formatter::getDiscussionLinkTemplate(). Keep in step with it.
const label = (href: string, inner: string) =>
  `<a href="${href}" rel="noopener" target="_self" class="UrlLink UrlLink--internal UrlLink--discussion">${inner}</a>`;

const favicon = '<img src="https://discuss.flarum.org/assets/favicon.png" class="UrlLink-favicon" alt="" aria-hidden="true">';
const discussion = '<span class="UrlLink-discussion">#38941</span>';
const post = '<span class="UrlLink-post"><i class="icon fas fa-comment UrlLink-postIcon" aria-hidden="true"></i>44</span>';

describe('selectedText', () => {
  describe('a link to a discussion, shown as a label', () => {
    test('is quoted as the address the writer pasted', () => {
      expect(quote(`<p>I’ve already created a GitHub : ${label(URL, favicon + discussion + post)}</p>`)).toBe(
        `I’ve already created a GitHub : ${URL}`
      );
    });

    test('is quoted as the address when it has no favicon or post number', () => {
      expect(quote(`<p>See ${label('https://discuss.flarum.org/d/38941-friendsofflarum-move-posts', discussion)}</p>`)).toBe(
        'See https://discuss.flarum.org/d/38941-friendsofflarum-move-posts'
      );
    });

    test('is quoted as the address whatever else the label holds', () => {
      const card = `<div class="UrlCard-title">Move posts</div><p class="UrlCard-excerpt">An extension that moves posts between discussions.</p>`;

      // In a div: block content inside a link inside a paragraph is split into
      // several links by the HTML parser.
      expect(quote(`<div>${label(URL, favicon + card)}</div>`)).toBe(URL);
    });

    test('is quoted as the address when the selection only reaches into the label', () => {
      const $body = postBody(`<p>Look at ${label(URL, favicon + discussion + post)}</p>`);
      const text = $body.find('p')[0].firstChild!;
      const partial = $body.find('.UrlLink-discussion')[0].firstChild!;

      select((range) => {
        range.setStart(text, 0);
        range.setEnd(partial, 3);
      });

      expect(selectedText($body)).toBe(`Look at ${URL}`);
    });

    test('is quoted with the address exactly as it is, not parsed as HTML', () => {
      // `&copy` without a semicolon is still an entity to the HTML parser.
      const href = 'https://discuss.flarum.org/d/38941?a=1&copy=2';

      expect(quote(`<p>${label(href, discussion)}</p>`)).toBe(href);
    });
  });

  describe('anything else is quoted as before', () => {
    test('an internal link with words of its own stays a Markdown link', () => {
      expect(quote(`<p><a href="${URL}" class="UrlLink UrlLink--internal">the move posts thread</a></p>`)).toBe(`[the move posts thread](${URL})`);
    });

    test('an external link stays a Markdown link', () => {
      expect(quote('<p><a href="https://example.com/page" class="UrlLink">a page</a></p>')).toBe('[a page](https://example.com/page)');
    });

    test('an image stays a Markdown image', () => {
      expect(quote('<p><img src="https://example.com/cat.png" alt="a cat"></p>')).toBe('![](https://example.com/cat.png)');
    });

    test('an emoji becomes its shortcode', () => {
      expect(quote('<p>Nice <img class="emoji" alt=":tada:" src="https://example.com/tada.png"></p>')).toBe('Nice :tada:');
    });
  });
});
