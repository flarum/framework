import * as string from '../../../../src/common/utils/string';

describe('string', () => {
  it('should slugify a string', () => {
    const stringToSlugify = 'Hello, world!';
    expect(string.slug(stringToSlugify)).toBe('hello-world');
  });

  it('should slugify a string with a custom slugging mode', () => {
    const stringToSlugify = 'Hello, world!';
    expect(string.slug(stringToSlugify, string.SluggingMode.UTF8)).toBe('hello-world');
  });

  it('should slugify a string with a custom slugging mode', () => {
    const stringToSlugify = 'Hello, world!';
    expect(string.slug(stringToSlugify, string.SluggingMode.ALPHANUMERIC)).toBe('hello-world');
  });

  it('should leave the default slugging mode unchanged', () => {
    // Extensions build ASCII-only slugs with the default mode; its output must not change.
    expect(string.slug('Tecnología')).toBe('tecnolog-a');
    expect(string.slug('Café Новости')).toBe('caf');
    expect(string.slug('Новости')).toBe('');
  });

  describe('UTF8_LATIN_ASCII slugging mode', () => {
    const slug = (name: string) => string.slug(name, string.SluggingMode.UTF8_LATIN_ASCII);

    it('should drop accents from Latin letters', () => {
      expect(slug('Tecnología')).toBe('tecnologia');
      expect(slug('Inversión y Señales')).toBe('inversion-y-senales');
      expect(slug('Tiếng Việt')).toBe('tieng-viet');
      expect(slug('İstanbul')).toBe('istanbul');
    });

    it('should spell out Latin letters that have no accent to drop', () => {
      expect(slug('Straße')).toBe('strasse');
      expect(slug('Øl og Æbler')).toBe('ol-og-aebler');
      expect(slug('Łódź')).toBe('lodz');
      expect(slug('Þórður Œuvre Đà Nẵng')).toBe('thordur-oeuvre-da-nang');
    });

    it('should keep letters from other scripts', () => {
      expect(slug('Новости')).toBe('новости');
      expect(slug('Ελληνικά')).toBe('ελληνικά');
      expect(slug('العربية')).toBe('العربية');
      expect(slug('新闻')).toBe('新闻');
      expect(slug('ニュース')).toBe('ニュース');
      expect(slug('한국어 뉴스')).toBe('한국어-뉴스');
      expect(slug('हिन्दी')).toBe('हिन्दी');
    });

    it('should handle mixed scripts and punctuation', () => {
      expect(slug('Café Новости')).toBe('cafe-новости');
      expect(slug('  Hello, World! 2026 ')).toBe('hello-world-2026');
    });
  });

  it('should make the first character of a string uppercase', () => {
    const stringToUppercase = 'hello, world!';
    expect(string.ucfirst(stringToUppercase)).toBe('Hello, world!');
  });

  it('should transform a camel case string to snake case', () => {
    const stringToTransform = 'helloWorld';
    expect(string.camelCaseToSnakeCase(stringToTransform)).toBe('hello_world');
  });
});
