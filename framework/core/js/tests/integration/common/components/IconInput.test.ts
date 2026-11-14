import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { app } from '../../../../src/forum';
import IconInput from '../../../../src/common/components/IconInput';
import Stream from '../../../../src/common/utils/Stream';
import bidi from '../../../../src/common/utils/bidi';
import fontAwesomeIcons, { fontAwesomeStyles, fontAwesomeIconsFor, type FontAwesomeStyle } from '../../../../src/common/utils/fontAwesomeIcons';
import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';

// The shape of Font Awesome's own stylesheets, as Iconography.less inlines
// them: the icons, the bundled Free faces with the classes that select them,
// then brands, whose icons follow the brands @font-face.
const STYLESHEET = `
  .fa-exclamation{--fa:"\\!"}
  .fa-house-chimney{--fa:"\\e3af"}
  .fa-house{--fa:"\\f015"}
  .fa-comment-alt,.fa-message{--fa:"\\f27a"}
  .fa-commenting{--fa:"\\f27a"}
  :root{--fa-family-classic:'Font Awesome 7 Free'}
  @font-face{font-family:"Font Awesome 7 Free";font-weight:900;src:url(s.woff2)}
  .fas{--fa-family:var(--fa-family-classic);--fa-style:900}
  @font-face{font-family:"Font Awesome 7 Free";font-weight:400;src:url(r.woff2)}
  .far{--fa-family:var(--fa-family-classic);--fa-style:400}
  :root{--fa-family-brands:'Font Awesome 7 Brands'}
  @font-face{font-family:"Font Awesome 7 Brands";font-weight:400;src:url(b.woff2)}
  .fab,.fa-brands{--fa-family:var(--fa-family-brands);--fa-style:400}
  .fa-github{--fa:"\\f09b"}
  .fa-github-square,.fa-square-github{--fa:"\\f092"}
`;

// What a Font Awesome Pro Kit adds once its script has run: the Pro faces,
// the classes for every family and weight, and the classic family moved to
// the Pro font. Sharp Regular is selectable but has no font, and the Kit's own
// custom icons (fak) are not a style of the icon list.
const KIT_STYLESHEET = `
  :root{--fa-family-classic:"Font Awesome 7 Pro";--fa-family-duotone:"Font Awesome 7 Duotone";--fa-family-sharp:"Font Awesome 7 Sharp"}
  @font-face{font-family:"Font Awesome 7 Pro";font-weight:900;src:url(p9.woff2)}
  @font-face{font-family:"Font Awesome 7 Pro";font-weight:400;src:url(p4.woff2)}
  @font-face{font-family:"Font Awesome 7 Pro";font-weight:300;src:url(p3.woff2)}
  @font-face{font-family:"Font Awesome 7 Pro";font-weight:100;src:url(p1.woff2)}
  @font-face{font-family:"Font Awesome 7 Duotone";font-weight:900;src:url(d9.woff2)}
  @font-face{font-family:"Font Awesome 7 Sharp";font-weight:900;src:url(s9.woff2)}
  @font-face{font-family:"Font Awesome 7 Sharp";font-weight:300;src:url(s3.woff2)}
  .fas{--fa-family:var(--fa-family-classic);--fa-style:900}
  .far{--fa-family:var(--fa-family-classic);--fa-style:400}
  .fal{--fa-family:var(--fa-family-classic);--fa-style:300}
  .fat{--fa-style:100}
  .fa-classic,.fat{--fa-family:var(--fa-family-classic)}
  .fa-duotone,.fad{--fa-family:var(--fa-family-duotone);--fa-style:900}
  .fass{--fa-family:var(--fa-family-sharp);--fa-style:900}
  .fasr{--fa-family:var(--fa-family-sharp);--fa-style:400}
  .fasl{--fa-family:var(--fa-family-sharp);--fa-style:300}
  .fak{--fa-family:var(--fa-family-kit);--fa-style:400}
`;

/**
 * Choose a family in an open picker's dropdown. This environment keeps the
 * rendered value as an attribute, which withAttr reads before the live value
 * a browser would update, so it is dropped first.
 */
const chooseFamily = (input: any, family: string) => {
  (input.first('select.IconPicker-family') as HTMLSelectElement).removeAttribute('value');
  input.setValue('select.IconPicker-family', family);
};

/**
 * Open the picker. It loads on first use, so give the import a moment to
 * resolve before looking at it.
 */
const openPicker = async (input: any) => {
  input.click('.IconInput-toggle');
  await new Promise((resolve) => setTimeout(resolve, 0));
  input.redraw();
};

const addStylesheet = (css: string) => {
  const style = document.createElement('style');
  style.textContent = css;
  document.head.appendChild(style);
};

const style = (prefix: string) => fontAwesomeStyles().find((style) => style.prefix === prefix) as FontAwesomeStyle;

/**
 * A canvas on which only the given characters draw anything, so the bundled
 * Free font's coverage can be faked. The placeholder never draws.
 */
const canvasDrawing = (drawn: (char: string) => boolean) => {
  let last = '';

  return jest.spyOn(HTMLCanvasElement.prototype, 'getContext').mockReturnValue({
    canvas: { width: 0, height: 0 },
    clearRect: () => {},
    fillText: (char: string) => (last = char),
    getImageData: () => ({ data: new Uint8ClampedArray(28 * 28 * 4).fill(drawn(last) ? 255 : 0) }),
  } as any);
};

beforeAll(() => {
  bootstrapForum();
  (document as any).fonts ??= { load: async () => [] };
  addStylesheet(STYLESHEET);
});

afterEach(() => jest.restoreAllMocks());

describe('fontAwesomeIcons', () => {
  it('reads classic and brand icons from the loaded stylesheet', () => {
    const { classic, brands } = fontAwesomeIcons();

    // "!" is defined first, but plain characters are listed after pictograms.
    expect(classic.map((icon) => icon.name)).toEqual(['house-chimney', 'house', 'message', 'exclamation']);
    expect(brands.map((icon) => icon.name)).toEqual(['github', 'square-github']);
  });

  it('turns aliases into search words instead of duplicates', () => {
    const message = fontAwesomeIcons().classic.find((icon) => icon.name === 'message')!;

    expect(message.search).toContain('comment-alt');
    expect(message.search).toContain('commenting');
  });

  it('offers the styles the bundled Free fonts can draw', () => {
    expect(fontAwesomeStyles().map((style) => style.prefix)).toEqual(['fas', 'far', 'fab']);
    expect(style('far')).toEqual({ prefix: 'far', family: 'classic', weight: 400, font: 'Font Awesome 7 Free' });
  });

  it("offers Solid and Regular when only their fonts can be read, as after core's rescue of a failed CDN or Kit", () => {
    // The rescue injects the Free faces alone; the classes that select them
    // came with the stylesheet that failed. Font Awesome's own short classes
    // are assumed, and only offered for a font that is there.
    const sheets = Array.from(document.head.querySelectorAll('style'));
    sheets.forEach((sheet) => sheet.remove());

    addStylesheet(`
      .fa-house{--fa:"\\f015"}
      @font-face{font-family:"Font Awesome 7 Free";font-weight:400;src:url(r.woff2)}
      @font-face{font-family:"Font Awesome 7 Free";font-weight:900;src:url(s.woff2)}
    `);

    try {
      expect(fontAwesomeStyles().map((style) => style.prefix)).toEqual(['fas', 'far']);
    } finally {
      document.head.querySelectorAll('style').forEach((sheet) => sheet.remove());
      sheets.forEach((sheet) => document.head.appendChild(sheet));
    }
  });

  it('offers only the Free Regular icons the Regular font draws', async () => {
    canvasDrawing((char) => char === '\uf015');

    expect(((await fontAwesomeIconsFor(style('far'))) as { name: string }[]).map((icon) => icon.name)).toEqual(['house']);
  });

  it('offers every classic icon as Regular when only plain characters draw (a Kit or CDN, before its fonts)', async () => {
    // The bundled faces are then a blank stand-in: Font Awesome's glyphs draw
    // nothing, but a plain "!" falls back to a system font and draws. A new
    // stylesheet has the fonts measured again.
    addStylesheet('');
    canvasDrawing((char) => char.codePointAt(0)! < 0x80 && char !== '\uf8fe');

    expect(((await fontAwesomeIconsFor(style('far'))) as { name: string }[]).map((icon) => icon.name)).toEqual(
      fontAwesomeIcons().classic.map((icon) => icon.name)
    );
  });

  it('decodes both hex and character escapes', () => {
    const { classic } = fontAwesomeIcons();

    expect(classic.find((icon) => icon.name === 'house')!.char).toBe('');
    expect(classic.find((icon) => icon.name === 'exclamation')!.char).toBe('!');
  });
});

describe('IconInput', () => {
  // First to open a picker: once loaded, the picker stays loaded.
  it('loads the picker when it is first opened, showing that it is loading', async () => {
    const input = mq(IconInput, { bidi: Stream('') });

    input.click('.IconInput-toggle');

    expect(input).toHaveElement('.IconInput .LoadingIndicator');

    await new Promise((resolve) => setTimeout(resolve, 0));
    input.redraw();

    expect(input).toHaveElement('.IconPicker');
    expect(input).not.toHaveElement('.IconInput > .LoadingIndicator');
  });

  it('renders a text field and a preview of the current icon', () => {
    const input = mq(m(IconInput, { value: 'fas fa-bolt', onchange: () => {} }));

    expect(input).toHaveElement('input.FormControl');
    expect(input).toHaveElement('.IconInput-toggle i.fas.fa-bolt');
  });

  it('shows the value in a field that opens the picker rather than being typed in', async () => {
    const input = mq(IconInput, { value: 'fas fa-bolt', onchange: () => {} });

    expect(input).toHaveElement('input.FormControl[readonly]');

    input.focus('input.FormControl');
    await new Promise((resolve) => setTimeout(resolve, 0));
    input.redraw();

    expect(input).toHaveElement('.IconPicker');
  });

  it('opens the picker when the field is clicked', async () => {
    const input = mq(IconInput, { value: 'fas fa-bolt', onchange: () => {} });

    input.click('input.FormControl');
    await new Promise((resolve) => setTimeout(resolve, 0));
    input.redraw();

    expect(input).toHaveElement('.IconPicker');
  });

  it('previews a class the picker does not list, such as a Kit icon', () => {
    const input = mq(IconInput, { value: 'fa-kit fa-my-custom-icon', onchange: () => {} });

    expect(input).toHaveElement('.IconInput-toggle i.fa-kit.fa-my-custom-icon');
  });

  it('previews a neutral icon, not the placeholder, while empty', () => {
    const input = mq(IconInput, { value: '', placeholder: 'fas fa-bolt', onchange: () => {} });

    expect(input).toHaveElement('.IconInput-toggle i.fa-icons');
    expect(input).not.toHaveElement('.IconInput-toggle i.fa-bolt');
  });

  it('opens the picker and fills a bidi-bound field with the icon picked', async () => {
    // Bound with the real bidi utility, as EditGroupModal and FormGroup bind it.
    const icon = Stream('');
    const input = mq({ view: () => bidi(m(IconInput, { bidi: icon }), icon) });

    expect(input).not.toHaveElement('.IconPicker');

    await openPicker(input);
    expect(input).toHaveElement('.IconPicker');
    expect(input).toHaveElement('.IconPicker-icon[title="fas fa-house"]');

    input.click('.IconPicker-icon[title="fas fa-house"]');

    expect(icon()).toBe('fas fa-house');
    expect(input).not.toHaveElement('.IconPicker');
  });

  it("puts the picker's search in the field's place while it is open, and the field back after", async () => {
    const input = mq(IconInput, { value: 'fas fa-bolt', onchange: () => {} });

    await openPicker(input);

    expect(input).not.toHaveElement('input.FormControl[type=text]');
    expect(input).toHaveElement('.IconPicker-bar .IconInput-toggle');
    expect(input).toHaveElement('.IconPicker-bar input[type=search]');

    input.click('.IconPicker-icon[title="fas fa-house"]');

    expect(input).not.toHaveElement('.IconPicker');
    expect(input).toHaveElement('input.FormControl[type=text]');
  });

  it('closes when its toggle is pressed again', async () => {
    const input = mq(IconInput, { bidi: Stream('') });

    await openPicker(input);
    input.click('.IconPicker-bar .IconInput-toggle');

    expect(input).not.toHaveElement('.IconPicker');
    expect(input).toHaveElement('input.FormControl[type=text]');
  });

  it('lists the current icon first', async () => {
    const input = mq(IconInput, { value: 'fas fa-message', onchange: () => {} });

    await openPicker(input);

    expect((input.first('.IconPicker-icon') as HTMLElement).title).toBe('fas fa-message');
    expect(input).toHaveElement('.IconPicker-icon.active[title="fas fa-message"]');
  });

  it('reports a picked icon to a plain change handler too', async () => {
    const values: string[] = [];
    const input = mq(IconInput, { value: '', onchange: (e: { target: { value: string } }) => values.push(e.target.value) });

    await openPicker(input);
    input.click('.IconPicker-icon[title="fas fa-house"]');

    expect(values).toEqual(['fas fa-house']);
  });

  it('searches names and aliases', async () => {
    const input = mq(IconInput, { bidi: Stream('') });

    await openPicker(input);
    input.setValue('.IconPicker input[type=search]', 'commenting');

    expect(input).toHaveElement('.IconPicker-icon[title="fas fa-message"]');
    expect(input).not.toHaveElement('.IconPicker-icon[title="fas fa-house"]');
  });

  it('finds icons when the search includes the prefix', async () => {
    const input = mq(IconInput, { bidi: Stream('') });

    await openPicker(input);
    input.setValue('.IconPicker input[type=search]', 'fas fa-house');

    expect(input).toHaveElement('.IconPicker-icon[title="fas fa-house"]');
    expect(input).not.toHaveElement('.IconPicker-icon[title="fas fa-message"]');
  });

  it('lists an exact name first', async () => {
    const input = mq(IconInput, { bidi: Stream('') });

    await openPicker(input);
    // house-chimney is defined first, but "house" is an exact match.
    input.setValue('.IconPicker input[type=search]', 'house');

    expect((input.first('.IconPicker-icon') as HTMLElement).title).toBe('fas fa-house');
  });

  it('picks the first match on Enter instead of submitting a surrounding form', async () => {
    const values: string[] = [];
    const input = mq(IconInput, { value: '', onchange: (e: { target: { value: string } }) => values.push(e.target.value) });

    await openPicker(input);
    input.setValue('.IconPicker input[type=search]', 'house');

    let enter: Event | undefined;
    (input.first('.IconPicker input[type=search]') as HTMLElement).addEventListener('keydown', (e) => (enter = e));
    input.keydown('.IconPicker input[type=search]', 'enter', { key: 'Enter', cancelable: true });

    expect(enter?.defaultPrevented).toBe(true);
    expect(values).toEqual(['fas fa-house']);
  });

  /** Type in the open picker's search box and press Enter. */
  const enter = (input: any, text: string) => {
    input.setValue('.IconPicker input[type=search]', text);
    input.keydown('.IconPicker input[type=search]', 'enter', { key: 'Enter', cancelable: true });
  };

  it('uses a typed class that matches no icon, such as a Kit icon, spaces and all', async () => {
    const values: string[] = [];
    const input = mq(IconInput, { value: '', onchange: (e: { target: { value: string } }) => values.push(e.target.value) });

    await openPicker(input);
    input.setValue('.IconPicker input[type=search]', 'fa-kit   fa-my-icon');

    expect(input).toHaveElement('.IconPicker-use');

    enter(input, 'fa-kit   fa-my-icon');

    expect(values).toEqual(['fa-kit fa-my-icon']);
  });

  it('offers to use a typed class even when similar icons match', async () => {
    const values: string[] = [];
    const input = mq(IconInput, { value: '', onchange: (e: { target: { value: string } }) => values.push(e.target.value) });

    await openPicker(input);
    input.setValue('.IconPicker input[type=search]', 'fas fa-hous');
    input.click('.IconPicker-use');

    expect(values).toEqual(['fas fa-hous']);
  });

  it('does not offer to use a plain search word as a class', async () => {
    const input = mq(IconInput, { bidi: Stream('') });

    await openPicker(input);
    input.setValue('.IconPicker input[type=search]', 'rocket');

    expect(input).not.toHaveElement('.IconPicker-use');
  });

  it('picks the matching icon when the full class of one is typed', async () => {
    const values: string[] = [];
    const input = mq(IconInput, { value: '', onchange: (e: { target: { value: string } }) => values.push(e.target.value) });

    await openPicker(input);
    enter(input, 'fas fa-house');

    expect(values).toEqual(['fas fa-house']);
  });

  it('removes the icon', async () => {
    const values: string[] = [];
    const input = mq(IconInput, { value: 'fas fa-house', onchange: (e: { target: { value: string } }) => values.push(e.target.value) });

    await openPicker(input);
    input.click('.IconPicker-remove');

    expect(values).toEqual(['']);
    expect(input).not.toHaveElement('.IconPicker');
  });

  it('has nothing to remove while there is no icon', async () => {
    const input = mq(IconInput, { bidi: Stream('') });

    await openPicker(input);

    expect(input).not.toHaveElement('.IconPicker-remove');
  });

  it('picks from what was typed when Enter comes before the next redraw', async () => {
    const values: string[] = [];
    const input = mq(IconInput, { value: 'fas fa-house', onchange: (e: { target: { value: string } }) => values.push(e.target.value) });

    await openPicker(input);

    // Typed and entered at once, as with a paste: no redraw in between.
    const search = input.first('.IconPicker input[type=search]') as HTMLInputElement;
    search.value = 'message';
    search.dispatchEvent(new (search.ownerDocument.defaultView as any).Event('input'));
    input.keydown('.IconPicker input[type=search]', 'enter', { key: 'Enter', cancelable: true });

    expect(values).toEqual(['fas fa-message']);
  });

  it('closes on Escape before a surrounding modal hears it', async () => {
    const input = mq(IconInput, { bidi: Stream('') });
    const modal = jest.fn();
    document.body.addEventListener('keyup', modal);

    await openPicker(input);
    document.body.dispatchEvent(new KeyboardEvent('keyup', { key: 'Escape', bubbles: true }));
    input.redraw();

    expect(input).not.toHaveElement('.IconPicker');
    expect(modal).not.toHaveBeenCalled();

    document.body.removeEventListener('keyup', modal);
  });

  it('closes on a click outside it, including on another picker toggle', async () => {
    const input = mq(IconInput, { bidi: Stream('') });
    const other = document.createElement('button');
    other.className = 'IconInput-toggle';
    document.body.appendChild(other);

    await openPicker(input);
    other.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
    input.redraw();

    expect(input).not.toHaveElement('.IconPicker');
    other.remove();
  });

  it('offers brands with the fab prefix', async () => {
    const input = mq(IconInput, { bidi: Stream('') });

    await openPicker(input);
    chooseFamily(input, 'brands');

    expect(input).toHaveElement('.IconPicker-icon[title="fab fa-github"]');
  });
});

describe('with a Font Awesome Pro Kit', () => {
  beforeAll(() => addStylesheet(KIT_STYLESHEET));

  it('offers every family and weight that has a font, in a stable order', () => {
    expect(fontAwesomeStyles().map((style) => style.prefix)).toEqual(['fas', 'far', 'fal', 'fat', 'fad', 'fass', 'fasl', 'fab']);
    expect(style('fasl')).toEqual({ prefix: 'fasl', family: 'sharp', weight: 300, font: 'Font Awesome 7 Sharp' });
  });

  it('offers every icon in a Pro style, without measuring the font', () => {
    const getContext = jest.spyOn(HTMLCanvasElement.prototype, 'getContext');

    expect(fontAwesomeIconsFor(style('far'))).toEqual(fontAwesomeIcons().classic);
    expect(getContext).not.toHaveBeenCalled();
  });

  it('shows the families, and the weights of the family chosen', async () => {
    const input = mq(IconInput, { bidi: Stream('') });

    await openPicker(input);

    const labels = (selector: string) => (input.find(selector) as HTMLElement[]).map((element) => element.textContent);

    expect(labels('select.IconPicker-family option')).toEqual(['Classic', 'Duotone', 'Sharp', 'Brands']);
    expect(labels('.IconPicker-weights .Button')).toEqual(['Solid', 'Regular', 'Light', 'Thin']);

    chooseFamily(input, 'sharp');

    expect(labels('.IconPicker-weights .Button')).toEqual(['Solid', 'Light']);
  });

  it('picks an icon in the family and weight chosen', async () => {
    const icon = Stream('');
    const input = mq({ view: () => bidi(m(IconInput, { bidi: icon }), icon) });

    await openPicker(input);
    chooseFamily(input, 'sharp');
    input.click('.IconPicker-weights .Button:nth-child(2)');
    input.click('.IconPicker-icon[title="fasl fa-house"]');

    expect(icon()).toBe('fasl fa-house');
  });

  it('opens on the style of the current icon, short or long form', async () => {
    for (const value of ['fasl fa-house', 'fa-sharp fa-light fa-house']) {
      const input = mq(IconInput, { value, onchange: () => {} });

      await openPicker(input);

      expect(input).toHaveElement('.IconPicker-icon.active[title="fasl fa-house"]');
    }
  });

  it('switches to the style a typed class names, and picks the icon in it', async () => {
    const values: string[] = [];
    const input = mq(IconInput, { value: '', onchange: (e: { target: { value: string } }) => values.push(e.target.value) });

    await openPicker(input);
    input.setValue('.IconPicker input[type=search]', 'fa-sharp fa-light fa-house');

    expect((input.first('select.IconPicker-family') as HTMLSelectElement).value).toBe('sharp');
    expect(input).toHaveElement('.IconPicker-weights .Button.active');
    expect((input.first('.IconPicker-weights .Button.active') as HTMLElement).textContent).toBe('Light');

    input.keydown('.IconPicker input[type=search]', 'enter', { key: 'Enter', cancelable: true });

    expect(values).toEqual(['fasl fa-house']);
  });

  it('searches by the icon name in a typed class', async () => {
    const input = mq(IconInput, { bidi: Stream('') });

    await openPicker(input);
    input.setValue('.IconPicker input[type=search]', 'fa-sharp fa-light fa-house');

    expect(input).toHaveElement('.IconPicker-icon[title="fasl fa-house"]');
    expect(input).not.toHaveElement('.IconPicker-icon[title="fas fa-message"]');
  });

});

describe('in a forum that forces an icon style', () => {
  beforeAll(() => app.boot());

  afterEach(() => app.forum.pushAttributes({ fontAwesomeForcedStyle: null }));

  it('shows each style as itself, and says so', async () => {
    app.forum.pushAttributes({ fontAwesomeForcedStyle: 'fa-regular' });

    const input = mq(IconInput, { bidi: Stream('') });

    await openPicker(input);
    chooseFamily(input, 'sharp');

    expect(input).toHaveElement('.IconPicker-forced');
    expect(input).toHaveElement('.IconPicker-icon[title="fass fa-house"] i.fass.fa-house');
    expect(input).not.toHaveElement('.IconPicker-icon i.fa-regular');
  });

  it('says nothing for brands, which are never restyled', async () => {
    app.forum.pushAttributes({ fontAwesomeForcedStyle: 'fa-regular' });

    const input = mq(IconInput, { bidi: Stream('') });

    await openPicker(input);
    chooseFamily(input, 'brands');

    expect(input).not.toHaveElement('.IconPicker-forced');
  });

  it('says nothing when no style is forced', async () => {
    const input = mq(IconInput, { bidi: Stream('') });

    await openPicker(input);

    expect(input).not.toHaveElement('.IconPicker-forced');
  });
});

describe('in a forum whose assets are served from another domain', () => {
  const base = 'https://cdn.example.com/assets';
  let real: StyleSheet[];

  /**
   * A stylesheet the page loaded but can't read: served from another domain,
   * without a CORS request.
   */
  const unreadable = (href: string) =>
    ({
      href,
      get cssRules(): CSSRuleList {
        throw new DOMException('Not allowed to access cross-origin stylesheet', 'SecurityError');
      },
    }) as unknown as StyleSheet;

  const pageSheets = (sheets: StyleSheet[]) => jest.spyOn(Document.prototype, 'styleSheets', 'get').mockReturnValue(sheets as any);

  beforeAll(() => {
    app.boot();
    real = Array.from(document.styleSheets);
  });

  beforeEach(() => app.forum.pushAttributes({ assetsBaseUrl: base }));

  afterEach(() => {
    delete (globalThis as any).fetch;
  });

  it("fetches the forum's own stylesheet with CORS and lists its icons", async () => {
    const fetch = jest.fn(async () => ({ ok: true, text: async () => '.fa-rocket{--fa:"\\f135"}' }));
    (globalThis as any).fetch = fetch;
    pageSheets([...real, unreadable(`${base}/forum.css?v=1`)]);

    const input = mq(IconInput, { bidi: Stream('') });
    await openPicker(input);

    expect(fetch).toHaveBeenCalledWith(`${base}/forum.css?v=1`, { mode: 'cors', credentials: 'omit', cache: 'no-store' });
    expect(input).toHaveElement('.IconPicker-icon[title="fas fa-rocket"]');
  });

  it("leaves other sites' stylesheets alone", async () => {
    const fetch = jest.fn();
    (globalThis as any).fetch = fetch;
    pageSheets([...real, unreadable('https://fonts.example.org/families.css')]);

    const input = mq(IconInput, { bidi: Stream('') });
    await openPicker(input);

    expect(fetch).not.toHaveBeenCalled();
    expect(input).toHaveElement('.IconPicker-icon[title="fas fa-house"]');
  });

  it("says why there are no icons when the stylesheet can't be fetched either, and still takes a typed class", async () => {
    (globalThis as any).fetch = jest.fn(async () => {
      throw new TypeError('Failed to fetch');
    });
    pageSheets([unreadable(`${base}/forum.css?v=2`)]);

    const values: string[] = [];
    const input = mq(IconInput, { value: '', onchange: (e: { target: { value: string } }) => values.push(e.target.value) });
    await openPicker(input);

    expect(input).toHaveElement('.IconPicker-unreadable');
    expect(input).not.toHaveElement('.IconPicker-grid');

    input.setValue('.IconPicker input[type=search]', 'fas fa-house');
    input.click('.IconPicker-use');

    expect(values).toEqual(['fas fa-house']);
  });
});
