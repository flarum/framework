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

  describe('mention icons are decorative', () => {
    const crest = '<img class="icon IconImage" src="https://example.com/crest.webp" alt="">';

    test('a group mention keeps its name without quoting its icon as an image', () => {
      expect(quote(`<p>Hello <span class="GroupMention"><span class="GroupMention-name">@Fans</span>${crest}</span></p>`)).toBe('Hello @Fans');
    });

    test('a selection entirely inside a mention also omits its icon', () => {
      const $body = postBody(`<p><span class="GroupMention"><span class="GroupMention-name">@Fans</span>${crest}</span></p>`);
      select((range) => range.selectNodeContents($body.find('.GroupMention')[0]));

      expect(selectedText($body)).toBe('@Fans');
    });

    test('a tag mention keeps its link without quoting its icon as an image', () => {
      expect(
        quote(`<p>See <a href="https://example.com/t/lions" class="TagMention"><span class="TagMention-text">${crest}Lions</span></a></p>`)
      ).toBe('See [Lions](https://example.com/t/lions)');
    });
  });

  describe('text from the post is quoted as text, never parsed as HTML', () => {
    // What the server renders for `[<img src=x onerror=…>](https://example.com)`:
    // the link text is escaped.
    const markup = '&lt;img src=x onerror=&quot;window.quoted = 1&quot;&gt;';
    const tag = '<img src=x onerror="window.quoted = 1">';

    test('a link whose text looks like a tag', () => {
      expect(quote(`<p>Interesting link: <a href="https://example.com/" class="UrlLink">${markup}</a> worth a read.</p>`)).toBe(
        `Interesting link: [${tag}](https://example.com/) worth a read.`
      );
    });

    test('a link whose text looks like an entity', () => {
      expect(quote('<p><a href="https://example.com/" class="UrlLink">AT&amp;amp;T</a></p>')).toBe('[AT&amp;T](https://example.com/)');
    });

    test('an image whose address looks like it holds an entity', () => {
      expect(quote('<p><img src="https://example.com/a.png?x=1&amp;copy;y"></p>')).toBe('![](https://example.com/a.png?x=1&copy;y)');
    });

    test('an emoji whose shortcode looks like a tag', () => {
      expect(quote(`<p>Nice <img class="emoji" alt="${markup}" src="https://example.com/tada.png"></p>`)).toBe(`Nice ${tag}`);
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

describe('spoilers', () => {
  test('an inline spoiler stays a spoiler', () => {
    expect(quote('<p>before <span class="spoiler" data-spoiler="">secret</span> after</p>')).toBe('before >!secret!< after');
  });

  test('a revealed inline spoiler stays a spoiler', () => {
    expect(quote('<p>before <span data-spoiler="">secret</span> after</p>')).toBe('before >!secret!< after');
  });

  test('a block spoiler stays a spoiler', () => {
    expect(quote('<details class="spoiler">one\ntwo</details>')).toBe('>! one\n>! two\n');
  });

  test('a literal !< does not close the spoiler', () => {
    expect(quote('<p><span class="spoiler" data-spoiler="">a !&lt; b</span></p>')).toBe('>!a !\\< b!<');
    expect(quote('<details class="spoiler">a !&lt; b</details>')).toBe('>! a !\\< b\n');
  });

  test('keeps images, emoji and links', () => {
    expect(
      quote(
        '<p><span class="spoiler" data-spoiler=""><img src="https://example.com/a.png"> <img class="emoji" alt="😀" src="e.png"> <a href="https://example.com/">site</a></span></p>'
      )
    ).toBe('>!![](https://example.com/a.png) 😀 [site](https://example.com/)!<');
    expect(quote('<details class="spoiler"><img src="https://example.com/a.png"></details>')).toBe('>! ![](https://example.com/a.png)\n');
  });
});
