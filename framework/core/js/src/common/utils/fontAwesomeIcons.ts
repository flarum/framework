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

export interface FontAwesomeStyle {
  /** The class that selects the style, such as `fas` or `fasl`. */
  prefix: string;
  /** The family: `classic`, `duotone`, `sharp`, `sharp-duotone`, `brands`… */
  family: string;
  /** The font weight: 900 solid, 400 regular, 300 light, 100 thin. */
  weight: number;
  /** The font the family draws with, such as "Font Awesome 7 Pro". */
  font: string;
}

/**
 * Everything read from the page's stylesheets. A Kit adds its stylesheet
 * after the page loads, so this is read again whenever the page's
 * stylesheets change.
 */
interface Read {
  sheets: StyleSheet[];
  icons: FontAwesomeIconSets;
  styles: FontAwesomeStyle[];
}

let read: Read | null = null;
const coverage = new Map<string, Promise<FontAwesomeIcon[]>>();

/**
 * The rules of stylesheets the page can't read, fetched again with CORS, by
 * URL. `null` where that failed too.
 */
const fetched = new Map<string, CSSRuleList | null>();

const SINGLE_CLASS = /^\.fa-[a-z0-9-]+$/;

// The short classes that select a style: fas, fal, fad, fass, fasdl… but not
// .fa itself, nor an icon (.fa-house).
const STYLE_CLASS = /^\.fa[a-z]{1,5}$/;

// Families whose classes don't draw the icon list: a Kit's own uploaded icons.
const OTHER_FAMILIES = ['kit', 'kit-duotone'];

// The order families are offered in. Others follow, then brands.
const FAMILY_ORDER = ['classic', 'duotone', 'sharp', 'sharp-duotone'];

// Solid first, as the default, then lighter.
const WEIGHT_ORDER = [900, 400, 300, 100];

// What the classic family draws with when no stylesheet names it.
const DEFAULT_CLASSIC_FONT = 'Font Awesome 7 Free';

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

function unquote(value: string): string {
  return value.trim().replace(/^["']|["']$/g, '');
}

/** The weights a `font-weight` descriptor covers: one, or a range such as `100 900`. */
function weightRange(value: string): [number, number] {
  const named: Record<string, number> = { normal: 400, bold: 700 };
  const [min, max = min] = (value.trim() || 'normal').split(/\s+/).map((part) => named[part] ?? parseInt(part, 10));

  return [min, max];
}

function readStylesheets(): Read {
  const classic = new Map<string, FontAwesomeIcon>();
  const brands = new Map<string, FontAwesomeIcon>();

  // What each style class selects, and what each family variable names. Later
  // rules win, as they do in the cascade: a Kit's classes and variables
  // override the bundled ones. Font Awesome's own short classes are known
  // from the start, for when the stylesheet that defines them can't be read
  // or never arrived, such as after core rescues a failed CDN or Kit with the
  // bundled fonts alone. They are still only offered for a font that's there.
  const classes = new Map<string, { family?: string; weight?: number }>([
    ['fas', { family: 'classic', weight: 900 }],
    ['far', { family: 'classic', weight: 400 }],
    ['fab', { family: 'brands', weight: 400 }],
  ]);
  const families = new Map<string, string>();
  const faces: { font: string; min: number; max: number }[] = [];

  for (const sheet of Array.from(document.styleSheets)) {
    let rules: CSSRuleList;

    try {
      rules = (sheet as CSSStyleSheet).cssRules;
    } catch (e) {
      const refetched = sheet.href ? fetched.get(sheet.href) : null;

      if (!refetched) continue;

      rules = refetched;
    }

    let inBrands = false;

    for (const rule of Array.from(rules)) {
      if (rule.type === FONT_FACE_RULE) {
        const style = (rule as CSSFontFaceRule).style;
        const font = unquote(style.getPropertyValue('font-family'));
        const [min, max] = weightRange(style.getPropertyValue('font-weight'));

        faces.push({ font, min, max });
        inBrands = /brands/i.test(font);
        continue;
      }

      if (rule.type !== STYLE_RULE) continue;

      const style = (rule as CSSStyleRule).style;
      const selectors = (rule as CSSStyleRule).selectorText.split(',').map((selector) => selector.trim());

      for (const [, family, font] of style.cssText.matchAll(/--fa-family-([a-z-]+)\s*:\s*([^;]+)/g)) {
        families.set(family, unquote(font));
      }

      const family = style.getPropertyValue('--fa-family').match(/var\(\s*--fa-family-([a-z-]+)\s*\)/)?.[1];
      const weight = parseInt(style.getPropertyValue('--fa-style'), 10);

      if (family || weight) {
        for (const selector of selectors.filter((selector) => STYLE_CLASS.test(selector))) {
          const current = classes.get(selector.slice(1)) ?? {};

          classes.set(selector.slice(1), { family: family ?? current.family, weight: weight || current.weight });
        }
      }

      const glyph = style.getPropertyValue('--fa');
      if (!glyph) continue;

      if (!selectors.every((selector) => SINGLE_CLASS.test(selector))) continue;

      // In a combined rule the last selector is the current name and the
      // others are old aliases. An alias with a rule of its own shares its
      // glyph with the real icon, so it becomes a search word rather than a
      // duplicate. A Kit repeats the icons the bundled stylesheet defines, so
      // a name is only added once.
      const list = inBrands ? brands : classic;
      const char = decodeGlyph(glyph);
      const names = selectors.map((selector) => selector.slice(4));
      const existing = list.get(char);

      if (existing) {
        const words = existing.search.split(' ');
        existing.search = [...words, ...names.filter((name) => !words.includes(name))].join(' ');
      } else {
        list.set(char, { name: names[names.length - 1], char, search: names.join(' ') });
      }
    }
  }

  // Font Awesome also draws plain digits, letters and symbols (fa-0, fa-a,
  // fa-hashtag's "#"). They are real icons, but the least useful to browse,
  // so they come after the pictograms.
  const pictogramsFirst = (list: Map<string, FontAwesomeIcon>) => [...list.values()].sort((a, b) => Number(isPlain(a)) - Number(isPlain(b)));

  // A style is offered when its class selects a family and weight, and a font
  // for that family covers the weight.
  const styles: FontAwesomeStyle[] = [];

  for (const [prefix, { family, weight }] of classes) {
    if (!family || !weight || OTHER_FAMILIES.includes(family)) continue;

    const font = families.get(family) ?? (family === 'classic' ? DEFAULT_CLASSIC_FONT : undefined);

    if (font && faces.some((face) => face.font === font && face.min <= weight && weight <= face.max)) {
      styles.push({ prefix, family, weight, font });
    }
  }

  // Icons were read, so something draws them, even if no font for a classic
  // style could be: offer them as Solid, Font Awesome's default.
  if (classic.size && !styles.some((style) => style.family !== 'brands')) {
    styles.push({ prefix: 'fas', family: 'classic', weight: 900, font: families.get('classic') ?? DEFAULT_CLASSIC_FONT });
  }

  const familyRank = (family: string) =>
    family === 'brands' ? Infinity : FAMILY_ORDER.includes(family) ? FAMILY_ORDER.indexOf(family) : FAMILY_ORDER.length;
  const weightRank = (weight: number) => (WEIGHT_ORDER.includes(weight) ? WEIGHT_ORDER.indexOf(weight) : WEIGHT_ORDER.length);

  styles.sort(
    (a, b) => familyRank(a.family) - familyRank(b.family) || a.family.localeCompare(b.family) || weightRank(a.weight) - weightRank(b.weight)
  );

  return {
    sheets: Array.from(document.styleSheets),
    icons: { classic: pictogramsFirst(classic), brands: pictogramsFirst(brands) },
    styles,
  };
}

function current(): Read {
  const sheets = document.styleSheets;

  if (!read || read.sheets.length !== sheets.length || read.sheets.some((sheet, i) => sheet !== sheets[i])) {
    read = readStylesheets();
    coverage.clear();
  }

  return read;
}

function isPlain(icon: FontAwesomeIcon): boolean {
  return icon.char.codePointAt(0)! < 0x80;
}

/**
 * Fetch the stylesheets under the forum's asset URL that the page can't read,
 * so their icons can be listed. A stylesheet served from another domain, such
 * as a bucket or CDN, is loaded without CORS, and the browser keeps its rules
 * from scripts even where the host would allow them. Fetched again with CORS,
 * those rules can be read wherever the host sends the header for it.
 *
 * Returns `null` when there is nothing to fetch, so the icons can be read
 * straight away.
 */
export function fetchUnreadableStylesheets(assetsBaseUrl: string | null | undefined): Promise<void> | null {
  if (!assetsBaseUrl || typeof fetch === 'undefined') return null;

  const hrefs = Array.from(document.styleSheets)
    .filter((sheet) => {
      try {
        return !(sheet as CSSStyleSheet).cssRules;
      } catch (e) {
        return true;
      }
    })
    .map((sheet) => sheet.href)
    .filter((href): href is string => !!href && href.startsWith(assetsBaseUrl) && !fetched.has(href));

  if (!hrefs.length) return null;

  return Promise.all(
    hrefs.map((href) =>
      // Past the browser's cache: the page loaded the stylesheet without CORS,
      // and a host that only answers CORS headers to a CORS request (as R2
      // does, with Vary: Origin) left that copy without them.
      fetch(href, { mode: 'cors', credentials: 'omit', cache: 'no-store' })
        .then((response) => (response.ok ? response.text() : Promise.reject()))
        .then(
          (text) => fetched.set(href, parseStylesheet(text)),
          () => fetched.set(href, null)
        )
    )
  ).then(() => {
    read = null;
  });
}

/**
 * The rules of a stylesheet's text, parsed without styling anything on the
 * page or loading its fonts again.
 */
function parseStylesheet(text: string): CSSRuleList | null {
  try {
    const sheet = new CSSStyleSheet();
    sheet.replaceSync(text);

    return sheet.cssRules;
  } catch (e) {
    // Without constructable stylesheets, parse it in a <style> that applies
    // to nothing, and take it out again.
    const style = document.createElement('style');

    style.media = 'not all';
    style.textContent = text;
    document.head.appendChild(style);

    const rules = style.sheet?.cssRules ?? null;
    style.remove();

    return rules;
  }
}

/**
 * The Font Awesome icons this forum can draw, read from the stylesheets it has
 * already loaded. There is no icon list to download, and the result always
 * matches the Font Awesome version in use, including a Kit's.
 *
 * Every icon is a rule like `.fa-house{--fa:"\f015"}`. Brand icons follow the
 * brands `@font-face`, which is what tells a brand from a classic icon.
 *
 * Stylesheets served from another domain can only be read once fetched again
 * with CORS: see `fetchUnreadableStylesheets()`.
 */
export default function fontAwesomeIcons(): FontAwesomeIconSets {
  return current().icons;
}

/**
 * The styles this forum can draw icons in: each family (classic, duotone,
 * sharp, brands…) at each weight (solid, regular, light, thin) that a loaded
 * font covers. The bundled Free fonts give classic Solid and Regular, and
 * brands; a Pro Kit adds the rest.
 */
export function fontAwesomeStyles(): FontAwesomeStyle[] {
  return current().styles;
}

/**
 * The icons a style draws. Every icon has a Solid style, and a Pro font draws
 * every icon in every style, but only a few hundred free icons have a Regular
 * style. So the other weights of a Free font are measured, which takes a
 * moment: that answer comes as a promise.
 */
export function fontAwesomeIconsFor(style: FontAwesomeStyle): FontAwesomeIcon[] | Promise<FontAwesomeIcon[]> {
  const list = style.family === 'brands' ? fontAwesomeIcons().brands : fontAwesomeIcons().classic;

  if (style.family === 'brands' || style.weight === 900 || !/\bFree\b/.test(style.font)) return list;

  const key = `${style.weight} ${style.font}`;

  if (!coverage.has(key)) coverage.set(key, drawable(list, `${style.weight} 24px "${style.font}"`));

  return coverage.get(key)!;
}

/**
 * The icons a font draws. A Free font draws one shared placeholder for every
 * icon it lacks — the same shape it draws for a code point no icon uses — so
 * an icon is drawn exactly when it differs from that placeholder.
 */
async function drawable(list: FontAwesomeIcon[], font: string): Promise<FontAwesomeIcon[]> {
  await document.fonts.load(font, '').catch(() => {});

  const size = 28;
  const context = document.createElement('canvas').getContext('2d', { willReadFrequently: true });

  if (!context) return list;

  context.canvas.width = context.canvas.height = size;
  context.textBaseline = 'top';
  context.font = font;

  const pixels = (char: string) => {
    context.clearRect(0, 0, size, size);
    context.fillText(char, 2, 2);

    return new Uint32Array(context.getImageData(0, 0, size, size).data.buffer);
  };

  const placeholder = pixels('');
  const found = list.filter((icon) => pixels(icon.char).some((value, i) => value !== placeholder[i]));

  // While a Kit or CDN supplies the fonts, the bundled Free faces are a blank
  // stand-in: no icon draws, but plain characters such as "!" fall back to a
  // system font and do. Measured by pictograms alone, nothing was found: the
  // remote fonts decide the coverage there, so offer every icon.
  return found.some((icon) => !isPlain(icon)) ? found : list;
}
