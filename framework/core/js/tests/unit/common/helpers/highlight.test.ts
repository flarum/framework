import highlight from '../../../../src/common/helpers/highlight';

describe('highlight', () => {
  it('should return the string if no phrase or length is given', () => {
    const string = 'Hello, world!';
    expect(highlight(string)).toBe(string);
  });

  it('should highlight a phrase in a string', () => {
    const string = 'Hello, world!';
    const phrase = 'world';

    // @ts-ignore
    expect(highlight(string, phrase).children).toBe('Hello, <mark>world</mark>!');
  });

  it('should highlight a phrase in a string case-insensitively', () => {
    const string = 'Hello, world!';
    const phrase = 'WORLD';

    // @ts-ignore
    expect(highlight(string, phrase).children).toBe('Hello, <mark>world</mark>!');
  });

  it('should escape plain text, and highlight within it', () => {
    // @ts-ignore
    expect(highlight('Write <username> & more', 'more').children).toBe('Write &lt;username&gt; &amp; <mark>more</mark>');
  });

  it('should not match inside the entities that escaping adds', () => {
    // @ts-ignore
    expect(highlight('Tom & Jerry', 'amp').children).toBe('Tom &amp; Jerry');
    // @ts-ignore
    expect(highlight('a < b', 'lt').children).toBe('a &lt; b');
  });

  it('should escape matched text', () => {
    // @ts-ignore
    expect(highlight('x <b> y', /<b>/g).children).toBe('x <mark>&lt;b&gt;</mark> y');
  });

  it('should escape truncated plain text', () => {
    // @ts-ignore
    expect(highlight('<b> world', 'world', 100).children).toBe('&lt;b&gt; <mark>world</mark>');
  });

  it('should highlight only the first match for a non-global regular expression', () => {
    // @ts-ignore
    expect(highlight('foo foo', /foo/i).children).toBe('<mark>foo</mark> foo');
  });

  it('should highlight only text, not tags or attributes, in safe HTML', () => {
    const html = '<p><a href="https://example.com/class.png">a class</a></p>';

    // @ts-ignore
    expect(highlight(html, /class|png|href/gi, undefined, true).children).toBe('<p><a href="https://example.com/class.png">a <mark>class</mark></a></p>');
  });

  it('should render safe HTML as-is without a phrase', () => {
    // @ts-ignore
    expect(highlight('<b>x</b>', undefined, 100, true).children).toBe('<b>x</b>');
  });
});
