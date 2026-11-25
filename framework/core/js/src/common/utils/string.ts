/**
 * Truncate a string to the given length, appending ellipses if necessary.
 */
export function truncate(string: string, length: number, start: number = 0): string {
  return (start > 0 ? '...' : '') + string.substring(start, start + length) + (string.length > start + length ? '...' : '');
}

/**
 * Create a slug out of the given string depending on the selected mode.
 * Invalid characters are converted to hyphens. `UTF8_FOLDED` removes some
 * instead, such as invisible characters and the marks it drops.
 *
 * NOTE: This method does not use the comparably sophisticated transliteration
 * mechanism that is employed in the backend. Therefore, it should only be used
 * to *suggest* slugs that can be overridden by the user.
 */
export function slug(string: string, mode: SluggingMode = SluggingMode.ALPHANUMERIC): string {
  switch (mode) {
    case SluggingMode.UTF8:
      return (
        string
          .toLowerCase()
          // Match non-word characters (take UTF8 into consideration) and replace with a dash.
          .replace(/[^\p{L}\p{N}\p{M}]/giu, '-')
          .replace(/-+/g, '-')
          .replace(/-$|^-/g, '')
      );

    case SluggingMode.UTF8_FOLDED:
      return (
        // Emoji first, before some of them (ℹ️, ™, ㊙️, 🈁) fold into letters.
        // Compatibility forms then become the letters they stand for:
        // fullwidth ＰＣ, the ﬁ ligature, ①, ² and halfwidth katakana. Only in
        // the blocks made for them, because NFKC also rewrites ordinary
        // letters of some scripts, such as Thai and Lao ำ and Hangul jamo.
        Array.from(string.replace(/\p{Extended_Pictographic}/gu, ' '), (character) =>
          COMPATIBILITY_FORMS.test(character) ? foldCompatibilityForm(character) : character
        )
          .join('')
          .toLowerCase()
          // Decomposed, so that both encodings of a letter like ǿ are handled
          // the same, and its accent can be dropped.
          .normalize('NFD')
          // Invisible characters: variation selectors after emoji, soft
          // hyphens, Hangul fillers and the Arabic tatweel. Joiners stay
          // inside a word, where they are part of its spelling (Sinhala
          // ශ්‍රී, Persian بی‌بی‌سی, Devanagari क्‌ष), and go everywhere else.
          .replace(/[\p{Cf}\p{Default_Ignorable_Code_Point}\u{0640}]/gu, (character, offset: number, text: string) =>
            isJoinerInWord(character, offset, text) ? character : ''
          )
          .replace(/[ßæœøłđðþıəħŧŋ]/gu, (letter) => LATIN_LETTERS[letter])
          // Latin letters lose their accents. Hebrew and Arabic vowel and
          // reading marks are optional and usually left out, so they go too.
          // In other scripts, such as Devanagari and Thai, marks are part of
          // the letter and stay.
          .replace(/(\p{Script=Latin})\p{M}+/gu, '$1')
          .replace(HEBREW_AND_ARABIC_VOWEL_MARKS, '')
          // Marks with no letter to belong to, such as the keycap in 1️⃣.
          .replace(/(^|[^\p{L}\p{M}])\p{M}+/gu, '$1')
          .normalize('NFC')
          .replace(/[^\p{L}\p{N}\p{M}\u{200C}\u{200D}]+/gu, '-')
          .replace(/-$|^-/g, '')
      );

    case SluggingMode.ALPHANUMERIC:
    default:
      return string
        .toLowerCase()
        .replace(/[^a-z0-9]/gi, '-')
        .replace(/-+/g, '-')
        .replace(/-$|^-/g, '');
  }
}

export enum SluggingMode {
  ALPHANUMERIC = 'alphanum',
  UTF8 = 'utf8',
  /**
   * Like `UTF8`, with the name folded to a stable, readable form: Latin
   * letters lose their accents and the common ones without a decomposition are
   * spelled out (ß gives ss), compatibility forms such as fullwidth letters
   * and ligatures become plain ones, and invisible characters and Hebrew and
   * Arabic vowel marks are dropped. Other scripts keep their letters and marks,
   * so the result is not ASCII: "Café Новости" gives "cafe-новости".
   */
  UTF8_FOLDED = 'utf8_folded',
}

/**
 * Latin letters that have no decomposition into a base letter and an accent.
 */
const LATIN_LETTERS: Record<string, string> = {
  ß: 'ss',
  æ: 'ae',
  œ: 'oe',
  ø: 'o',
  ł: 'l',
  đ: 'd',
  ð: 'd',
  þ: 'th',
  ı: 'i',
  ə: 'e',
  ħ: 'h',
  ŧ: 't',
  ŋ: 'ng',
};

/**
 * Hebrew points and cantillation, and Arabic vowel and Quranic marks. Not
 * the madda and hamzas (U+0653 to U+0655, and Kashmiri's wavy hamza below,
 * U+065F), which are part of letters like أ.
 */
const HEBREW_AND_ARABIC_VOWEL_MARKS =
  /[\u{0591}-\u{05BD}\u{05BF}\u{05C1}\u{05C2}\u{05C4}\u{05C5}\u{05C7}\u{0610}-\u{061A}\u{064B}-\u{0652}\u{0656}-\u{065E}\u{0670}\u{06D6}-\u{06DC}\u{06DF}-\u{06E4}\u{06E7}\u{06E8}\u{06EA}-\u{06ED}\u{08F0}-\u{08F2}]/gu;

/**
 * Characters folded with NFKC: Latin, Greek and shared ones, and the blocks of
 * presentation and width variants.
 */
const COMPATIBILITY_FORMS =
  /[\p{Script=Latin}\p{Script=Greek}\p{Script=Common}\p{Script=Inherited}\u{2F00}-\u{2FDF}\u{3200}-\u{33FF}\u{F900}-\u{FAFF}\u{FB00}-\u{FDFF}\u{FE70}-\u{FEFF}\u{FF00}-\u{FFEF}]/u;

/**
 * A compatibility form as the character it stands for. Halfwidth Hangul jamo
 * become the jamo typed on a Korean keyboard (ㄱ), not the conjoining ones
 * NFKC gives. NFKC puts a space before a mark it spaces out, such as the
 * voiced mark ゛: dropping it lets the mark join its letter again.
 */
function foldCompatibilityForm(character: string): string {
  const code = character.codePointAt(0)!;

  if (code >= 0xffa0 && code <= 0xffdc) return halfwidthJamo(code);

  const folded = character.normalize('NFKC').replace(/^ (?=\p{M})/u, '');

  return /[\u{1100}-\u{11FF}]/u.test(folded) ? character : folded;
}

/**
 * The compatibility jamo (U+3131 on) for a halfwidth one: the same order,
 * with gaps between the halfwidth vowel rows. The filler stays a filler.
 */
function halfwidthJamo(code: number): string {
  const rows: [number, number, number][] = [
    [0xffa0, 0xffa0, 0x3164],
    [0xffa1, 0xffbe, 0x3131],
    [0xffc2, 0xffc7, 0x314f],
    [0xffca, 0xffcf, 0x3155],
    [0xffd2, 0xffd7, 0x315b],
    [0xffda, 0xffdc, 0x3161],
  ];
  const row = rows.find(([first, last]) => code >= first && code <= last);

  return String.fromCodePoint(row ? row[2] + code - row[0] : code);
}

/**
 * Whether a zero-width joiner or non-joiner sits inside a word, after a
 * letter or mark and before a letter. Not after a letter of a script whose
 * letters never join, where it changes nothing visible and would only give
 * names that look the same different slugs.
 */
function isJoinerInWord(character: string, offset: number, text: string): boolean {
  if (character !== '\u200C' && character !== '\u200D') return false;

  // The letter before it, past any marks on it: after NFD, é is e and a mark.
  const before = text.slice(0, offset).match(/(\p{L})?\p{M}*$/u)?.[1] || '';
  const after = text.slice(offset + 1).match(/^[^]/u)?.[0] || '';

  return !!before && !NON_JOINING_SCRIPTS.test(before) && /\p{L}/u.test(after);
}

const NON_JOINING_SCRIPTS =
  /[\p{Script=Latin}\p{Script=Greek}\p{Script=Cyrillic}\p{Script=Han}\p{Script=Hiragana}\p{Script=Katakana}\p{Script=Hangul}]/u;

/**
 * Strip HTML tags and quotes out of the given string, replacing them with
 * meaningful punctuation.
 */
export function getPlainContent(string: string): string {
  const html = string.replace(/(<\/p>|<br>)/g, '$1 &nbsp;').replace(/<img\b[^>]*>/gi, ' ');

  const element = new DOMParser().parseFromString(html, 'text/html').documentElement;

  getPlainContent.removeSelectors.forEach((selector) => {
    const el = element.querySelectorAll(selector);
    el.forEach((e) => {
      e.remove();
    });
  });

  return element.innerText.replace(/\s+/g, ' ').trim();
}

/**
 * An array of DOM selectors to remove when getting plain content.
 *
 * @type {Array}
 */
getPlainContent.removeSelectors = ['blockquote', 'script'];

/**
 * Make a string's first character uppercase.
 */
export function ucfirst(string: string): string {
  return string.substr(0, 1).toUpperCase() + string.substr(1);
}

/**
 * Transform a camel case string to snake case.
 */
export function camelCaseToSnakeCase(str: string): string {
  return str.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);
}

/**
 * Generate a random string (a-z, 0-9) of a given length.
 *
 * Providing a length of less than 0 will result in an error.
 *
 * @param length Length of the random string to generate
 * @returns A random string of provided length
 */
export function generateRandomString(length: number): string {
  if (length < 0) throw new Error('Cannot generate a random string with length less than 0.');
  if (length === 0) return '';

  const arr = new Uint8Array(length / 2);
  window.crypto.getRandomValues(arr);

  return Array.from(arr, (dec) => {
    return dec.toString(16).padStart(2, '0');
  }).join('');
}
