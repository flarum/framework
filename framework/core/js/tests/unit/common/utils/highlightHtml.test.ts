import highlightHtml from '../../../../src/common/utils/highlightHtml';

describe('highlightHtml', () => {
  test('wraps matches in text with <mark>', () => {
    expect(highlightHtml('<p>An example here</p>', /example/gi)).toBe('<p>An <mark>example</mark> here</p>');
  });

  test('wraps every match, keeping their case', () => {
    expect(highlightHtml('<p>Foo and foo</p>', /foo/gi)).toBe('<p><mark>Foo</mark> and <mark>foo</mark></p>');
  });

  test('leaves attribute values untouched', () => {
    const html = '<p><img src="https://example.com/photo.png" alt=""></p>';

    expect(highlightHtml(html, /png/gi)).toBe(html);
  });

  test('leaves tag and attribute names untouched', () => {
    const html = '<p class="x"><a href="https://a.test/" rel="nofollow">link</a></p>';

    expect(highlightHtml(html, /class|href|rel|a/gi)).toBe('<p class="x"><a href="https://a.test/" rel="nofollow">link</a></p>');
  });

  test('highlights link text but not the href', () => {
    expect(highlightHtml('<a href="https://example.com/class.html">the class docs</a>', /class/gi)).toBe(
      '<a href="https://example.com/class.html">the <mark>class</mark> docs</a>'
    );
  });

  test('escapes matched and surrounding text', () => {
    expect(highlightHtml('<p>a &lt;b&gt; c</p>', /c/gi)).toBe('<p>a &lt;b&gt; <mark>c</mark></p>');
  });

  test('skips text in script and style elements', () => {
    const html = '<script>var foo = 1;</script><style>.foo {}</style>';

    expect(highlightHtml(html, /foo/gi)).toBe(html);
  });

  test('skips text in code blocks, but not inline code', () => {
    expect(highlightHtml('<pre><code>const foo = 1;</code></pre><p><code>foo</code></p>', /foo/gi)).toBe(
      '<pre><code>const foo = 1;</code></pre><p><code><mark>foo</mark></code></p>'
    );
  });

  test('skips text in SVG, MathML and raw-text elements', () => {
    const html = '<svg><text>foo</text></svg><math><mi>foo</mi></math><xmp>foo</xmp><noembed>foo</noembed>';

    expect(highlightHtml(html, /foo/gi)).toBe(html);
  });

  test('returns the HTML exactly as given when nothing matches', () => {
    const html = '<p><img src=x alt="a"  ></p>';

    expect(highlightHtml(html, /zzz/gi)).toBe(html);
  });

  test('ignores empty matches', () => {
    expect(highlightHtml('<p>abc</p>', /x*/gi)).toBe('<p>abc</p>');
  });

  test('wraps only the first match for a non-global regular expression', () => {
    expect(highlightHtml('<p>foo foo</p><p>foo</p>', /foo/i)).toBe('<p><mark>foo</mark> foo</p><p>foo</p>');
  });
});
