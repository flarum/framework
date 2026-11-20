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

  describe('UTF8_FOLDED slugging mode', () => {
    const slug = (name: string) => string.slug(name, string.SluggingMode.UTF8_FOLDED);

    it('should drop accents from Latin letters', () => {
      expect(slug('Tecnología')).toBe('tecnologia');
      expect(slug('Tiếng Việt')).toBe('tieng-viet');
      expect(slug('İstanbul')).toBe('istanbul');
    });

    it('should spell out Latin letters that have no accent to drop', () => {
      expect(slug('Straße')).toBe('strasse');
      expect(slug('Øl og Æbler')).toBe('ol-og-aebler');
      expect(slug('Łódź')).toBe('lodz');
      expect(slug('Azərbaycan')).toBe('azerbaycan');
      expect(slug('Ħamrun')).toBe('hamrun');
    });

    it('should give the same slug however a letter is encoded', () => {
      // ǿ as one code point, and as ø followed by a combining acute accent.
      expect(slug('\u01FF')).toBe('o');
      expect(slug('\u00F8\u0301')).toBe('o');
      // が as one code point, and as か followed by a combining voiced mark.
      expect(slug('\u304C')).toBe(slug('\u304B\u3099'));
      // Korean typed as jamo is recomposed into syllables.
      expect(slug('\u1112\u1161\u11AB')).toBe('한');
    });

    it('should fold compatibility forms into the letters they stand for', () => {
      expect(slug('ＰＣ Games')).toBe('pc-games');
      expect(slug('ﬁnance')).toBe('finance');
      expect(slug('ｶﾞｲﾄﾞ')).toBe('ガイド');
      expect(slug('Ⓐpps 𝐁old ſtyle')).toBe('apps-bold-style');
      expect(slug('Top²')).toBe('top2');
    });

    it('should keep letters that compatibility folding would rewrite in their own script', () => {
      // NFKC would split Thai and Lao ำ and turn Hangul jamo into their conjoining forms.
      expect(slug('น้ำ')).toBe('น้ำ');
      expect(slug('ຳ')).toBe('ຳ');
      expect(slug('ㄱㄴ')).toBe('ㄱㄴ');
    });

    it('should leave out emoji and the invisible characters that come with them', () => {
      // Emoji pickers add a variation selector (U+FE0F); keycaps add U+20E3.
      expect(slug('⚽\uFE0F Football')).toBe('football');
      expect(slug('Café ☕\uFE0F')).toBe('cafe');
      expect(slug('1\uFE0F\u20E3 Rules')).toBe('1-rules');
      expect(slug('👨\u200D👩\u200D👧 Family 👍🏽')).toBe('family');
      expect(slug('☕\uFE0F')).toBe('');
    });

    it('should remove characters that join or break words instead of hyphenating them', () => {
      // A zero-width non-joiner inside a Persian word.
      expect(slug('کتاب\u200Cها')).toBe('کتابها');
      expect(slug('Soft\u00ADware')).toBe('software');
      expect(slug('هـــلا')).toBe('هلا');
    });

    it('should leave no invisible characters in the slug', () => {
      // The Hangul filler is a letter and the grapheme joiner a mark, but both are invisible.
      expect(slug('a\u3164b')).toBe('ab');
      expect(slug('\u3164')).toBe('');
      expect(slug('\uFFA0')).toBe('');
      expect(slug('日\u034F本')).toBe('日本');
      expect(slug('ក\u17B4ខ')).toBe('កខ');
    });

    it('should not turn halfwidth or circled Hangul jamo into conjoining jamo', () => {
      // NFKC would give U+1100, a jamo with no vowel that renders differently.
      expect(slug('\uFFA1')).toBe('\uFFA1');
      // A circled jamo is a symbol, so it is left out like other symbols.
      expect(slug('\u3260 News')).toBe('news');
      expect(slug('\u326E')).toBe('가');
    });

    it('should drop Hebrew and Arabic vowel marks, and keep Devanagari and Thai ones', () => {
      expect(slug('שָׁלוֹם')).toBe('שלום');
      expect(slug('مَرْحَبًا')).toBe('مرحبا');
      expect(slug('الرَّحْمٰنِ')).toBe('الرحمن');
      // Hamza is part of the letter, not a vowel mark.
      expect(slug('أهلا')).toBe('أهلا');
      expect(slug('हिन्दी')).toBe('हिन्दी');
      expect(slug('ภาษาไทย')).toBe('ภาษาไทย');
    });

    it('should keep letters from other scripts', () => {
      expect(slug('Новости')).toBe('новости');
      expect(slug('Ελληνικά')).toBe('ελληνικά');
      expect(slug('العربية')).toBe('العربية');
      expect(slug('新闻')).toBe('新闻');
      expect(slug('ニュース')).toBe('ニュース');
      expect(slug('한국어 뉴스')).toBe('한국어-뉴스');
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
