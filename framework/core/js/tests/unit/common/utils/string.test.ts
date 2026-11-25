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

    it('should leave out emoji before folding, so that none turns into letters', () => {
      expect(slug('ℹ\uFE0F Info')).toBe('info');
      expect(slug('Flarum™ Tips')).toBe('flarum-tips');
      expect(slug('㊙\uFE0F Secret')).toBe('secret');
      expect(slug('🈁 Here')).toBe('here');
      // Enclosed and superscript letters and digits, and keycap digits, aren't emoji.
      expect(slug('Ⓐ ① x² 1\uFE0F\u20E3')).toBe('a-1-x2-1');
    });

    it('should join a mark that compatibility folding spaces out to its letter again', () => {
      expect(slug('カ゛イト゛')).toBe('ガイド');
      expect(slug('ك\uFE76تب')).toBe('كتب');
    });

    it('should keep joiners inside a word, where they are part of its spelling', () => {
      // Without the ZWJ the Sinhala conjunct falls apart; without the ZWNJ the
      // Persian letters join; without it the Devanagari pair becomes क्ष.
      expect(slug('ශ්\u200Dරී ලංකා')).toBe('ශ්\u200Dරී-ලංකා');
      expect(slug('بی\u200Cبی\u200Cسی فارسی')).toBe('بی\u200Cبی\u200Cسی-فارسی');
      expect(slug('क्\u200Cष')).toBe('क्\u200Cष');
    });

    it('should drop joiners outside a word, and in scripts whose letters never join', () => {
      expect(slug('\u200Dstart end\u200C')).toBe('start-end');
      expect(slug('Auf\u200Clage')).toBe('auflage');
      expect(slug('д\u200Cb 日\u200D本')).toBe('дb-日本');
      // Past the accent to the letter it's on: é is e and a mark after NFD.
      expect(slug('café\u200Cbar й\u200Cк')).toBe('cafebar-йк');
    });

    it('should remove characters that break words instead of hyphenating them', () => {
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

    it('should turn halfwidth Hangul jamo into the jamo typed on a Korean keyboard', () => {
      // NFKC would give U+1100, a jamo with no vowel that renders differently.
      expect(slug('\uFFA1')).toBe('ㄱ');
      expect(slug('\uFFC2\uFFDC')).toBe('ㅏㅣ');
      // A circled jamo is a symbol, so it is left out like other symbols.
      expect(slug('\u3260 News')).toBe('news');
      expect(slug('\u326E')).toBe('가');
    });

    it('should drop Hebrew and Arabic vowel marks, and keep Devanagari and Thai ones', () => {
      expect(slug('שָׁלוֹם')).toBe('שלום');
      expect(slug('مَرْحَبًا')).toBe('مرحبا');
      // The open tanwin goes like the ordinary one.
      expect(slug('ب\u08F1')).toBe(slug('ب\u064C'));
      expect(slug('الرَّحْمٰنِ')).toBe('الرحمن');
      // Hamza is part of the letter, not a vowel mark.
      expect(slug('أهلا')).toBe('أهلا');
      // Kashmiri's wavy hamza below is part of the letter, like the hamza below.
      expect(slug('\u0627\u065F')).toBe('\u0627\u065F');
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
