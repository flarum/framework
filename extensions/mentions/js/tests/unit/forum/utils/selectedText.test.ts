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

    test('a tag mention is quoted as a tag mention, without quoting its icon as an image', () => {
      expect(
        quote(`<p>See <a href="https://example.com/t/lions" class="TagMention"><span class="TagMention-text">${crest}Lions</span></a></p>`)
      ).toBe('See #lions');
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
  // What the markdown extension renders: see its ispoiler template and
  // Litedown's block spoiler. Keep in step with them.
  const inline = (inner: string) => `<span class="spoiler" data-spoiler="" onclick="removeAttribute('class')">${inner}</span>`;
  const revealed = (inner: string) => `<span data-spoiler="" onclick="removeAttribute('class')">${inner}</span>`;
  const block = (inner: string) => `<details class="spoiler">${inner}</details>`;

  test('an inline spoiler stays a spoiler', () => {
    expect(quote(`<p>before ${inline('secret')} after</p>`)).toBe('before >!secret!< after');
  });

  test('a revealed inline spoiler stays a spoiler', () => {
    expect(quote(`<p>before ${revealed('secret')} after</p>`)).toBe('before >!secret!< after');
  });

  test('a block spoiler stays a spoiler', () => {
    expect(quote(block('<p>one</p>\n\n<p>two</p>'))).toBe('>! one\n>! \n>! two\n');
  });

  test('a literal !< does not close the spoiler', () => {
    expect(quote(`<p>${inline('a !&lt; b')}</p>`)).toBe('>!a !\\< b!<');
    expect(quote(`<p>${inline('<em>wow!</em>&lt;3 secret')}</p>`)).toBe('>!wow!\\<3 secret!<');
    expect(quote(block('<p><strong>a!</strong>&lt;b</p>'))).toBe('>! a!\\<b\n');
    expect(quote(block('<p>a !&lt; b</p>'))).toBe('>! a !\\< b\n');
  });

  test('a backslash at the end does not escape the closing marker', () => {
    expect(quote(`<p>a ${inline('C:\\')} b</p>`)).toBe('a >!C:\\\\!< b');
    expect(quote(block('<p>C:\\</p>'))).toBe('>! C:\\\\\n');
  });

  test('an inline spoiler is not turned into a block spoiler when it starts a line', () => {
    // ">!" at the start of a line opens a block spoiler unless the inline
    // spoiler closes on that line with no ">" before it.
    expect(quote(`<p>${inline('2 &gt; 1')} public</p>`)).toBe('>!2 \\> 1!< public');
    expect(quote(`<p>first</p>\n<p>${inline('2 &gt; 1')} public</p>`)).toBe('first\n>!2 \\> 1!< public');
    expect(quote(`<p>${inline('one\ntwo')} public</p>`)).toBe('>!one two!< public');
  });

  test('keeps images, emoji and links', () => {
    expect(
      quote(
        `<p>${inline('<img src="https://example.com/a.png"> <img class="emoji" alt="😀" src="e.png"> <a href="https://example.com/">site</a>')}</p>`
      )
    ).toBe('>!![](https://example.com/a.png) 😀 [site](https://example.com/)!<');
    expect(quote(block('<p><img src="https://example.com/a.png"></p>'))).toBe('>! ![](https://example.com/a.png)\n');
  });

  test('an inline spoiler that starts with a link is not read as an image', () => {
    expect(quote(`<p>x ${inline('<a href="https://example.com/">site</a>')} y</p>`)).toBe('x >! [site](https://example.com/)!< y');
  });

  test('a spoiler inside a link stays a spoiler', () => {
    expect(quote(`<p>see <a href="https://example.com/">${inline('x')}</a></p>`)).toBe('see [>!x!<](https://example.com/)');
    expect(quote(`<p><a href="https://example.com/">a ${revealed('b')}</a></p>`)).toBe('[a >!b!<](https://example.com/)');
  });

  test('a selection that only reaches into a spoiler keeps the part it takes hidden', () => {
    const $body = postBody(`<p>before ${inline('secret')} after</p>`);
    const text = $body.find('p')[0].firstChild!;
    const secret = $body.find('.spoiler')[0].firstChild!;

    select((range) => {
      range.setStart(text, 0);
      range.setEnd(secret, 3);
    });

    expect(selectedText($body)).toBe('before >!sec!<');
  });

  describe('a selection entirely inside a spoiler stays hidden', () => {
    test('a word in an inline spoiler', () => {
      const $body = postBody(`<p>before ${inline('top secret')} after</p>`);
      const secret = $body.find('.spoiler')[0].firstChild!;

      select((range) => {
        range.setStart(secret, 4);
        range.setEnd(secret, 10);
      });

      expect(selectedText($body)).toBe('>!secret!<');
    });

    test('a word in a revealed inline spoiler', () => {
      const $body = postBody(`<p>before ${revealed('top secret')} after</p>`);
      const secret = $body.find('[data-spoiler]')[0].firstChild!;

      select((range) => {
        range.setStart(secret, 4);
        range.setEnd(secret, 10);
      });

      expect(selectedText($body)).toBe('>!secret!<');
    });

    test('all of an inline spoiler holding a link', () => {
      const $body = postBody(`<p>${inline('a <a href="https://example.com/">site</a>')}</p>`);

      select((range) => range.selectNodeContents($body.find('.spoiler')[0]));

      expect(selectedText($body)).toBe('>!a [site](https://example.com/)!<');
    });

    test('a word in a link in an inline spoiler', () => {
      const $body = postBody(`<p>${inline('a <a href="https://example.com/">site</a>')}</p>`);
      const site = $body.find('a')[0].firstChild!;

      select((range) => {
        range.setStart(site, 0);
        range.setEnd(site, 2);
      });

      expect(selectedText($body)).toBe('>!si!<');
    });

    test('all the text of an opened block spoiler', () => {
      const $body = postBody(block('<p>one</p>\n\n<p>two</p>'));

      select((range) => range.selectNodeContents($body.find('details')[0]));

      expect(selectedText($body)).toBe('>! one\n>! \n>! two\n');
    });

    test('a word in an opened block spoiler', () => {
      const $body = postBody(block('<p>one two</p>'));
      const text = $body.find('p')[0].firstChild!;

      select((range) => {
        range.setStart(text, 4);
        range.setEnd(text, 7);
      });

      expect(selectedText($body)).toBe('>! two\n');
    });

    test('text escaped as in any other spoiler', () => {
      const $body = postBody(`<p>${inline('a !&lt; 2 &gt; C:\\')}</p>`);

      select((range) => range.selectNodeContents($body.find('.spoiler')[0]));

      expect(selectedText($body)).toBe('>!a !\\< 2 \\> C:\\\\!<');
    });
  });

  test('a backslash just before a spoiler does not escape its opening marker', () => {
    expect(quote(`<p>C:\\${inline('secret')}</p>`)).toBe('C:\\\\>!secret!<');
    expect(quote(`<p><em>C:\\</em>${inline('secret')}</p>`)).toBe('C:\\\\>!secret!<');
    expect(quote(`<p>C:\\\\${inline('secret')}</p>`)).toBe('C:\\\\>!secret!<');
    expect(quote(`<p><a href="https://example.com/">C:\\${inline('x')}</a></p>`)).toBe('[C:\\\\>!x!<](https://example.com/)');
  });

  test('a spoiler inside a spoiler is written back once, not escaped as text', () => {
    expect(quote(block(`<p>a ${inline('b')} c</p>`))).toBe('>! a >!b!< c\n');
    expect(quote(block(`<p>a <a href="https://example.com/">${inline('C:\\')}</a></p>`))).toBe('>! a [>!C:\\\\!<](https://example.com/)\n');

    const $body = postBody(block(`<p>a ${inline('b')} c</p>`));
    select((range) => range.selectNodeContents($body.find('p')[0]));

    expect(selectedText($body)).toBe('>! a >!b!< c\n');
  });

  test('a backslash run split across formatting counts as one run', () => {
    expect(quote(`<p><em>\\</em><strong>\\</strong>${inline('secret')}</p>`)).toBe('\\\\>!secret!<');
    expect(quote(`<p><em>\\</em><strong>\\\\</strong>${inline('secret')}</p>`)).toBe('\\\\\\\\>!secret!<');
  });

  test('an inline spoiler inside an inline spoiler does not close it early', () => {
    expect(quote(`<p>before ${inline(`outer <a href="https://example.com/">${inline('inner')}</a> tail`)} after</p>`)).toBe(
      'before >!outer [inner](https://example.com/) tail!< after'
    );

    const $body = postBody(`<p>${inline(`a ${inline('b')} c`)}</p>`);
    select((range) => range.selectNodeContents($body.find('.spoiler')[0]));

    expect(selectedText($body)).toBe('>!a b c!<');
  });

  test('a selection inside a spoiler with no text in it is not quoted', () => {
    const $body = postBody(`<p>${inline('<img class="icon IconImage" src="https://example.com/crest.webp" alt="">')}</p>`);
    select((range) => range.selectNodeContents($body.find('.spoiler')[0]));

    expect(selectedText($body)).toBe('');
  });

  test('a spoiler around the whole post body is not one the selection is in', () => {
    document.body.innerHTML = `<details class="spoiler"><div class="Post-body"><p>public</p></div></details>`;
    const $body = $(document.body).find('.Post-body');

    select((range) => range.selectNodeContents($body.find('p')[0]));

    expect(selectedText($body)).toBe('public');
  });
});

describe('a selection that runs past the post body', () => {
  // As CommentPost renders a post: the side comes first, and the controls are
  // buttons, whose text cannot be selected (Button.less), and a closed menu.
  const post = (id: string, html: string) =>
    `<div class="PostStream-item" id="${id}"><article class="CommentPost Post"><div class="Post-container">` +
    `<div class="Post-side"></div><div class="Post-main">` +
    `<header class="Post-header"><a class="username">${id}</a></header>` +
    `<div class="Post-body">${html}</div>` +
    `<aside class="Post-actions"><ul><li><button class="Button">Reply</button></li><li><button class="Button">Like</button></li>` +
    `<li><ul class="Dropdown-menu" style="display: none"><li><a>Edit</a></li></ul></li></ul></aside>` +
    `</div></div></article></div>`;

  function posts() {
    document.body.innerHTML =
      '<style>.Button { user-select: none; }</style>' +
      `<div class="PostStream">${post('first', '<p>One.</p><p>Last one.</p>')}${post('second', '<p>Two.</p>')}` +
      '<div class="PostStream-item"><div class="ReplyPlaceholder"><div class="Post-container"><div class="Post-side"></div>' +
      '<div class="Post-main">Write a reply...</div></div></div></div></div>';

    return {
      $first: $(document.body).find('#first .Post-body'),
      $second: $(document.body).find('#second .Post-body'),
    };
  }

  // Chrome ends a triple click on the last paragraph at the next place a
  // selection can start, past the controls.
  test("quotes a triple click on a post's last paragraph, which Chrome ends in the next post", () => {
    const { $first } = posts();

    select((range) => {
      range.setStart($first.find('p')[1].firstChild!, 0);
      range.setEnd(document.querySelector('#second .Post-container')!, 0);
    });

    expect(selectedText($first)).toBe('Last one.');
  });

  test("quotes a triple click on the last post's last paragraph, which Chrome ends in the reply placeholder", () => {
    const { $second } = posts();

    select((range) => {
      range.setStart($second.find('p')[0].firstChild!, 0);
      range.setEnd(document.querySelector('.ReplyPlaceholder .Post-side')!, 0);
    });

    expect(selectedText($second)).toBe('Two.');
  });

  // With a footer, such as one saying who liked the post, Chrome ends it at
  // the start of the footer's text instead.
  test("quotes a triple click on the last paragraph of a post with a footer, which Chrome ends at the footer's start", () => {
    const { $first } = posts();
    document
      .querySelector('#first .Post-actions')!
      .insertAdjacentHTML('afterend', '<footer class="Post-footer"><ul><li>You like this.</li></ul></footer>');

    select((range) => {
      range.setStart($first.find('p')[1].firstChild!, 0);
      range.setEnd(document.querySelector('#first .Post-footer li')!.firstChild!, 0);
    });

    expect(selectedText($first)).toBe('Last one.');
  });

  test('quotes the post text when the rest of the selection only takes in hidden text and white space', () => {
    const { $first } = posts();
    document.querySelector('#first .Post-actions')!.insertAdjacentHTML('beforebegin', '\n  <span style="visibility: hidden">Hidden</span>\n');

    select((range) => {
      range.setStart($first.find('p')[1].firstChild!, 0);
      range.setEnd(document.querySelector('#second .Post-container')!, 0);
    });

    expect(selectedText($first)).toBe('Last one.');
  });

  test('quotes nothing when the rest of the selection takes in text the reader could select', () => {
    const { $first } = posts();
    document.querySelector('#first .Post-actions')!.insertAdjacentHTML('beforebegin', '<span>Shown</span>');

    select((range) => {
      range.setStart($first.find('p')[1].firstChild!, 0);
      range.setEnd(document.querySelector('#second .Post-container')!, 0);
    });

    expect(selectedText($first)).toBe('');
  });

  test('quotes the post text of a selection that starts just before it, with no text in between', () => {
    const { $first } = posts();
    document.querySelector('#first .Post-header')!.innerHTML = '<button class="Button">Menu</button>';

    select((range) => {
      range.setStart(document.querySelector('#first .Post-header')!, 0);
      range.setEnd($first.find('p')[0].firstChild!, 3);
    });

    expect(selectedText($first)).toBe('One');
  });

  test('quotes nothing from either post when a selection runs from one into the text of the other', () => {
    const { $first, $second } = posts();

    select((range) => {
      range.setStart($first.find('p')[1].firstChild!, 0);
      range.setEnd($second.find('p')[0].firstChild!, 3);
    });

    expect(selectedText($first)).toBe('');
    expect(selectedText($second)).toBe('');
  });

  test("quotes nothing when a selection runs from the post text into the next post's author", () => {
    const { $first } = posts();

    select((range) => {
      range.setStart($first.find('p')[1].firstChild!, 0);
      range.setEnd(document.querySelector('#second .username')!.firstChild!, 2);
    });

    expect(selectedText($first)).toBe('');
  });

  test("quotes nothing when a selection starts in the post's author", () => {
    const { $first } = posts();

    select((range) => {
      range.setStart(document.querySelector('#first .username')!.firstChild!, 0);
      range.setEnd($first.find('p')[0].firstChild!, 3);
    });

    expect(selectedText($first)).toBe('');
  });

  test('quotes nothing when neither end of the selection is in the post text', () => {
    const { $first } = posts();

    select((range) => {
      range.setStart(document.querySelector('#first .Post-header')!, 0);
      range.setEnd(document.querySelector('#second .Post-container')!, 0);
    });

    expect(selectedText($first)).toBe('');
  });

  test('quotes nothing when the selection only touches the end of the post text', () => {
    const { $first } = posts();
    const body = $first[0];

    select((range) => {
      range.setStart(body, body.childNodes.length);
      range.setEnd(document.querySelector('#second .Post-container')!, 0);
    });

    expect(selectedText($first)).toBe('');
  });

  test('leaves the selection itself as it is', () => {
    const { $first } = posts();
    const next = document.querySelector('#second .Post-container')!;

    select((range) => {
      range.setStart($first.find('p')[1].firstChild!, 0);
      range.setEnd(next, 0);
    });
    selectedText($first);

    expect(window.getSelection()!.getRangeAt(0).endContainer).toBe(next);
  });
});

// What the server renders, as on a 2.x forum with Mentions, Markdown and
// BBCode: blockquotes wrap their content in a div, and paragraphs are
// separated by blank lines. Keep in step with ConfigureMentions.
const postMention = (id: number, name: string, deleted = false) =>
  deleted
    ? `<span class="PostMention PostMention--deleted" data-id="${id}"><i class="icon fas fa-reply"></i>${name}</span>`
    : `<a href="https://example.com/d/1-test/1" class="PostMention" data-id="${id}"><i class="icon fas fa-reply"></i>${name}</a>`;
const userMention = (id: number, name: string) => `<a href="https://example.com/u/${name}" class="UserMention" data-id="${id}">@${name}</a>`;
const quoted = (...blocks: string[]) => `<blockquote class="uncited"><div>${blocks.join('\n\n')}</div></blockquote>`;

describe('selectedText keeps what a post quotes and mentions', () => {
  test('a quote of a quote keeps its depth, three levels down', () => {
    const html = [quoted(`<p>${postMention(1, 'Admin')} First</p>`, quoted('<p>Second</p>', quoted('<p>Third</p>'))), '<p>Reply</p>'].join('\n\n');

    expect(quote(html)).toBe('> @"Admin"#p1 First\n>\n> > Second\n> >\n> > > Third\n\nReply');
  });

  test('a quote with several paragraphs keeps them apart', () => {
    expect(quote(quoted('<p>One</p>', '<p>Two</p>'))).toBe('> One\n>\n> Two');
  });

  test('a selection that starts inside a quote of a quote keeps the depth from there', () => {
    const $body = postBody([quoted('<p>Outer</p>', quoted('<p>Inner text</p>')), '<p>After</p>'].join('\n\n'));
    const inner = $body.find('blockquote blockquote p')[0].firstChild!;

    select((range) => {
      range.setStart(inner, 6);
      range.setEnd($body.find('> p')[0].firstChild!, 5);
    });

    expect(selectedText($body)).toBe('> > text\n\nAfter');
  });

  test('a selection entirely inside one quote is quoted as its text', () => {
    const $body = postBody(quoted('<p>Outer</p>', quoted('<p>Inner text</p>')));

    select((range) => range.selectNodeContents($body.find('blockquote blockquote p')[0]));

    expect(selectedText($body)).toBe('Inner text');
  });

  test('a post mention is quoted as a mention, not a link', () => {
    expect(quote(`<p>${postMention(12, 'Ben Smith')} agreed</p>`)).toBe('@"Ben Smith"#p12 agreed');
  });

  test('a mention of a deleted post keeps its ID, so it stays a mention', () => {
    expect(quote(`<p>${postMention(99, '[deleted]', true)} said so</p>`)).toBe('@"[deleted]"#p99 said so');
  });

  test('a selection that starts inside a mention still quotes the mention', () => {
    const $body = postBody(`<p>${postMention(12, 'Ben Smith')} agreed</p>`);

    select((range) => {
      range.setStart($body.find('.PostMention')[0].lastChild!, 4);
      range.setEnd($body.find('p')[0].lastChild!, 7);
    });

    expect(selectedText($body)).toBe('@"Smith"#p12 agreed');
  });

  test('user, deleted user and group mentions are quoted as they are written', () => {
    const deleted = '<span class="UserMention UserMention--deleted" data-id="7">@[deleted]</span>';
    const group =
      '<span class="GroupMention GroupMention--colored" style="--color:#80349E;" data-id="4"><span class="GroupMention-name">@Mods</span><i class="icon fas fa-bolt"></i></span>';

    expect(quote(`<p>Hi ${userMention(3, 'Ben')}, ${deleted} and ${group}</p>`)).toBe('Hi @"Ben"#3, @"[deleted]"#7 and @"Mods"#g4');
  });

  test('a tag mention is quoted by its slug', () => {
    const tag = '<a href="https://example.com/t/how-to" class="TagMention" data-id="5"><span class="TagMention-text">How to</span></a>';

    expect(quote(`<p>See ${tag}</p>`)).toBe('See #how-to');
  });

  test('a mention rendered before mentions carried their ID is quoted as its name', () => {
    expect(quote('<p><a href="https://example.com/u/ben" class="UserMention">@Ben</a> hi</p>')).toBe('@Ben hi');
  });

  test('a code block keeps its fence and language, without the highlighter script', () => {
    const pre =
      '<pre><code class="language-php">echo "code";</code><script async src="https://cdn.example/loader.min.js"></script><script>window.hljsLoader.highlightBlocks();</script></pre>';

    expect(quote(`<p>Before</p>\n\n${pre}\n\n<p>After</p>`)).toBe('Before\n\n```php\necho "code";\n```\n\nAfter');
  });

  test('a code block that holds a fence gets a longer one', () => {
    expect(quote('<pre><code>```\nnested\n```</code></pre>')).toBe('````\n```\nnested\n```\n````');
  });

  test('inline code keeps its backticks, even around a backtick', () => {
    expect(quote('<p>Use <code>a`b</code> and <code>`x</code></p>')).toBe('Use ``a`b`` and `` `x ``');
  });

  test('a selection entirely inside code is quoted as code', () => {
    const $body = postBody('<pre><code class="language-js">let a = 1;\nlet b = 2;</code></pre>\n\n<p>Use <code>npm ci</code> here</p>');

    select((range) => range.selectNodeContents($body.find('pre code')[0]));
    expect(selectedText($body)).toBe('```js\nlet a = 1;\nlet b = 2;\n```');

    select((range) => range.selectNodeContents($body.find('p code')[0]));
    expect(selectedText($body)).toBe('`npm ci`');
  });

  test('a code block inside a quote stays code, one level down', () => {
    expect(quote(quoted('<p>Try:</p>', '<pre><code>one\ntwo</code></pre>'))).toBe('> Try:\n>\n> ```\n> one\n> two\n> ```');
  });

  test('emoji, images and links in a quote of a quote are kept', () => {
    const html = quoted(
      '<p><img class="emoji" alt="😀" src="https://example.com/1f600.png"> <img src="https://example.com/a.png"> <a href="https://example.com/x">x</a></p>'
    );

    expect(quote(html)).toBe('> 😀 ![](https://example.com/a.png) [x](https://example.com/x)');
  });

  test('a name holding "#p1 cannot make the quote mention another post or user', () => {
    expect(quote(`<p>${postMention(5, 'a"#p1 b')}</p>`)).toBe('@"a_ b"#p5');
    expect(quote(`<p>${userMention(5, 'x"#7')}</p>`)).toBe('@"x_"#5');
  });

  test('code inside a spoiler is not escaped, since backslashes are literal in code', () => {
    expect(quote('<p><span class="spoiler"><code>a\\b&lt;c&gt;</code> and a&lt;b</span></p>')).toBe('>!`a\\b<c>` and a\\<b!<');
    expect(quote('<details class="spoiler"><summary>S</summary><pre><code>if (a &lt; b) "\\n"</code></pre></details>')).toBe(
      '>! S\n>! \n>! ```\n>! if (a < b) "\\n"\n>! ```\n'
    );
  });

  test('a selection entirely inside code inside a spoiler stays code, inside the spoiler', () => {
    const $body = postBody('<p><span class="spoiler">see <code>a&lt;b</code></span></p>');

    select((range) => range.selectNodeContents($body.find('code')[0]));

    expect(selectedText($body)).toBe('>!`a<b`!<');
  });

  test('a quote inside a block spoiler keeps its marker', () => {
    expect(quote('<details class="spoiler"><p>x</p>\n\n<blockquote class="uncited"><div><p>inner</p></div></blockquote></details>')).toBe(
      '>! x\n>! \n>! > inner\n'
    );
  });

  test('a BBCode quote keeps its citation on its own line', () => {
    expect(quote('<blockquote><div><cite>Bob wrote:</cite>hello</div></blockquote>')).toBe('> Bob wrote:\n> hello');
  });

  test('a quote or code block with no blank line around it still starts and ends a block', () => {
    expect(quote('before<blockquote class="uncited"><div>x</div></blockquote>after')).toBe('before\n\n> x\n\nafter');
    expect(quote('before<pre><code>x</code></pre>after')).toBe('before\n\n```\nx\n```\n\nafter');
  });

  test('a code block keeps the language it was written with, not one highlight.js guessed', () => {
    expect(quote('<pre><code class="language-php hljs">echo 1;</code></pre>')).toBe('```php\necho 1;\n```');
    expect(quote('<pre><code class="hljs language-perl">no language</code></pre>')).toBe('```\nno language\n```');
  });
});
