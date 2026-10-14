export interface FontAwesomeIcon {
  /** The icon's current name, without the `fa-` prefix. */
  name: string;
  /** The glyph the icon draws. */
  char: string;
  /** Its name and any older aliases, space-separated, for searching. */
  search: string;
}

export interface FontAwesomeIconSets {
  classic: FontAwesomeIcon[];
  brands: FontAwesomeIcon[];
}

let icons: FontAwesomeIconSets | null = null;
let regular: Promise<FontAwesomeIcon[]> | null = null;

const SINGLE_CLASS = /^\.fa-[a-z0-9-]+$/;

// CSSRule.STYLE_RULE and CSSRule.FONT_FACE_RULE. Compared by number, because
// not every environment exposes the CSSFontFaceRule class to test against.
const STYLE_RULE = 1;
const FONT_FACE_RULE = 5;

/**
 * Turn the authored CSS string of a `--fa` value — `"\f015"`, `"\!"` or a
 * literal character — into the character it stands for.
 */
function decodeGlyph(value: string): string {
  const body = value.trim().replace(/^["']|["']$/g, '');
  const hex = body.match(/^\\([0-9a-f]{1,6})\s?$/i);

  if (hex) return String.fromCodePoint(parseInt(hex[1], 16));

  return body.startsWith('\\') ? body.slice(1) : body;
}

/**
 * The Font Awesome icons this forum can draw, read from the stylesheet it has
 * already loaded. There is no icon list to download, and the result always
 * matches the bundled Font Awesome version.
 *
 * Every icon is a rule like `.fa-house{--fa:"\f015"}`. Iconography.less inlines
 * fontawesome.css (the classic icons) and then brands.css, whose glyphs follow
 * the brands `@font-face` — which is what tells a brand from a classic icon.
 *
 * Stylesheets served from another origin (a Font Awesome Kit, or assets on a
 * CDN) cannot be read, so their icons are not listed. They can still be typed.
 */
export default function fontAwesomeIcons(): FontAwesomeIconSets {
  if (icons) return icons;

  const classic = new Map<string, FontAwesomeIcon>();
  const brands = new Map<string, FontAwesomeIcon>();

  for (const sheet of Array.from(document.styleSheets)) {
    let rules: CSSRuleList;

    try {
      rules = sheet.cssRules;
    } catch (e) {
      continue;
    }

    let inBrands = false;

    for (const rule of Array.from(rules)) {
      if (rule.type === FONT_FACE_RULE) {
        inBrands = /brands/i.test((rule as CSSFontFaceRule).style.getPropertyValue('font-family'));
        continue;
      }

      if (rule.type !== STYLE_RULE) continue;

      const glyph = (rule as CSSStyleRule).style.getPropertyValue('--fa');
      if (!glyph) continue;

      const selectors = (rule as CSSStyleRule).selectorText.split(',').map((selector) => selector.trim());
      if (!selectors.every((selector) => SINGLE_CLASS.test(selector))) continue;

      // In a combined rule the last selector is the current name and the
      // others are old aliases. An alias with a rule of its own shares its
      // glyph with the real icon, so it becomes a search word rather than a
      // duplicate.
      const list = inBrands ? brands : classic;
      const char = decodeGlyph(glyph);
      const names = selectors.map((selector) => selector.slice(4)).join(' ');
      const existing = list.get(char);

      if (existing) {
        existing.search += ' ' + names;
      } else {
        list.set(char, { name: selectors[selectors.length - 1].slice(4), char, search: names });
      }
    }
  }

  // Font Awesome also draws plain digits, letters and symbols (fa-0, fa-a,
  // fa-hashtag's "#"). They are real icons, but the least useful to browse,
  // so they come after the pictograms.
  const pictogramsFirst = (list: Map<string, FontAwesomeIcon>) =>
    [...list.values()].sort((a, b) => Number(a.char.codePointAt(0)! < 0x80) - Number(b.char.codePointAt(0)! < 0x80));

  icons = { classic: pictogramsFirst(classic), brands: pictogramsFirst(brands) };

  return icons;
}

/**
 * The classic icons that have a free Regular (outline) style. Only a few
 * hundred do.
 *
 * The Regular font draws one shared placeholder for every icon it lacks — the
 * same shape it draws for a code point no icon uses — so an icon has a Regular
 * style exactly when it draws differently from that placeholder.
 */
export function regularFontAwesomeIcons(): Promise<FontAwesomeIcon[]> {
  regular ??= (async () => {
    const font = '400 24px "Font Awesome 7 Free"';

    await document.fonts.load(font, '').catch(() => {});

    const size = 28;
    const context = document.createElement('canvas').getContext('2d', { willReadFrequently: true });

    if (!context) return [];

    context.canvas.width = context.canvas.height = size;
    context.textBaseline = 'top';
    context.font = font;

    const pixels = (char: string) => {
      context.clearRect(0, 0, size, size);
      context.fillText(char, 2, 2);

      return new Uint32Array(context.getImageData(0, 0, size, size).data.buffer);
    };

    const placeholder = pixels('');

    return fontAwesomeIcons().classic.filter((icon) => pixels(icon.char).some((value, i) => value !== placeholder[i]));
  })();

  return regular;
}
