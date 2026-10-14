import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import IconInput from '../../../../src/common/components/IconInput';
import Stream from '../../../../src/common/utils/Stream';
import bidi from '../../../../src/common/utils/bidi';
import fontAwesomeIcons, { regularFontAwesomeIcons } from '../../../../src/common/utils/fontAwesomeIcons';
import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';

// The shape of Font Awesome's own stylesheets, as Iconography.less inlines
// them: classic icons, then brands after the brands @font-face.
const STYLESHEET = `
  .fa-exclamation{--fa:"\\!"}
  .fa-house-chimney{--fa:"\\e3af"}
  .fa-house{--fa:"\\f015"}
  .fa-comment-alt,.fa-message{--fa:"\\f27a"}
  .fa-commenting{--fa:"\\f27a"}
  .fa-solid,.fas{font-weight:900}
  @font-face{font-family:"Font Awesome 7 Brands";src:url(x.woff2)}
  .fa-github{--fa:"\\f09b"}
  .fa-github-square,.fa-square-github{--fa:"\\f092"}
`;

beforeAll(() => {
  bootstrapForum();

  const style = document.createElement('style');
  style.textContent = STYLESHEET;
  document.head.appendChild(style);
});

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

  it('offers every classic icon as Regular when the bundled fonts draw nothing (a Kit or CDN)', async () => {
    // A canvas on which every character draws nothing, as Flarum's blank
    // stand-in font does while a Kit supplies the real fonts.
    (document as any).fonts ??= { load: async () => [] };
    jest.spyOn(HTMLCanvasElement.prototype, 'getContext').mockReturnValue({
      canvas: { width: 0, height: 0 },
      clearRect: () => {},
      fillText: () => {},
      getImageData: () => ({ data: new Uint8ClampedArray(28 * 28 * 4) }),
    } as any);

    expect((await regularFontAwesomeIcons()).map((icon) => icon.name)).toEqual(fontAwesomeIcons().classic.map((icon) => icon.name));
  });

  it('decodes both hex and character escapes', () => {
    const { classic } = fontAwesomeIcons();

    expect(classic.find((icon) => icon.name === 'house')!.char).toBe('');
    expect(classic.find((icon) => icon.name === 'exclamation')!.char).toBe('!');
  });
});

describe('IconInput', () => {
  it('renders a text field and a preview of the current icon', () => {
    const input = mq(m(IconInput, { value: 'fas fa-bolt', onchange: () => {} }));

    expect(input).toHaveElement('input.FormControl');
    expect(input).toHaveElement('.IconInput-toggle i.fas.fa-bolt');
  });

  // Typing goes straight to the field's own handlers, so check it with a
  // plain one: this environment does not wire `bidi` to typed events.
  const typed = () => {
    const values: string[] = [];
    const oninput = (e: { target: { value: string } }) => values.push(e.target.value);

    return { values, input: mq(IconInput, { value: '', oninput }) };
  };

  it('keeps what is typed, spaces included', () => {
    const { values, input } = typed();

    input.setValue('input.FormControl', 'fas fa-comments');

    expect(values).toEqual(['fas fa-comments']);
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

  it('opens the picker and fills a bidi-bound field with the icon picked', () => {
    // Bound with the real bidi utility, as EditGroupModal and FormGroup bind it.
    const icon = Stream('');
    const input = mq({ view: () => bidi(m(IconInput, { bidi: icon }), icon) });

    expect(input).not.toHaveElement('.IconPicker');

    input.click('.IconInput-toggle');
    expect(input).toHaveElement('.IconPicker');
    expect(input).toHaveElement('.IconPicker-icon[title="fas fa-house"]');

    input.click('.IconPicker-icon[title="fas fa-house"]');

    expect(icon()).toBe('fas fa-house');
    expect(input).not.toHaveElement('.IconPicker');
  });

  it('reports a picked icon to a plain change handler too', () => {
    const values: string[] = [];
    const input = mq(IconInput, { value: '', onchange: (e: { target: { value: string } }) => values.push(e.target.value) });

    input.click('.IconInput-toggle');
    input.click('.IconPicker-icon[title="fas fa-house"]');

    expect(values).toEqual(['fas fa-house']);
  });

  it('searches names and aliases', () => {
    const input = mq(IconInput, { bidi: Stream('') });

    input.click('.IconInput-toggle');
    input.setValue('.IconPicker input[type=search]', 'commenting');

    expect(input).toHaveElement('.IconPicker-icon[title="fas fa-message"]');
    expect(input).not.toHaveElement('.IconPicker-icon[title="fas fa-house"]');
  });

  it('finds icons when the search includes the prefix', () => {
    const input = mq(IconInput, { bidi: Stream('') });

    input.click('.IconInput-toggle');
    input.setValue('.IconPicker input[type=search]', 'fas fa-house');

    expect(input).toHaveElement('.IconPicker-icon[title="fas fa-house"]');
    expect(input).not.toHaveElement('.IconPicker-icon[title="fas fa-message"]');
  });

  it('lists an exact name first', () => {
    const input = mq(IconInput, { bidi: Stream('') });

    input.click('.IconInput-toggle');
    // house-chimney is defined first, but "house" is an exact match.
    input.setValue('.IconPicker input[type=search]', 'house');

    expect((input.first('.IconPicker-icon') as HTMLElement).title).toBe('fas fa-house');
  });

  it('picks the first match on Enter instead of submitting a surrounding form', () => {
    const values: string[] = [];
    const input = mq(IconInput, { value: '', onchange: (e: { target: { value: string } }) => values.push(e.target.value) });

    input.click('.IconInput-toggle');
    input.setValue('.IconPicker input[type=search]', 'house');

    let enter: Event | undefined;
    (input.first('.IconPicker input[type=search]') as HTMLElement).addEventListener('keydown', (e) => (enter = e));
    input.keydown('.IconPicker input[type=search]', 'enter', { key: 'Enter', cancelable: true });

    expect(enter?.defaultPrevented).toBe(true);
    expect(values).toEqual(['fas fa-house']);
  });

  it('closes on Escape before a surrounding modal hears it', () => {
    const input = mq(IconInput, { bidi: Stream('') });
    const modal = jest.fn();
    document.body.addEventListener('keyup', modal);

    input.click('.IconInput-toggle');
    document.body.dispatchEvent(new KeyboardEvent('keyup', { key: 'Escape', bubbles: true }));
    input.redraw();

    expect(input).not.toHaveElement('.IconPicker');
    expect(modal).not.toHaveBeenCalled();

    document.body.removeEventListener('keyup', modal);
  });

  it('closes on a click outside it, including on another picker toggle', () => {
    const input = mq(IconInput, { bidi: Stream('') });
    const other = document.createElement('button');
    other.className = 'IconInput-toggle';
    document.body.appendChild(other);

    input.click('.IconInput-toggle');
    other.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
    input.redraw();

    expect(input).not.toHaveElement('.IconPicker');
    other.remove();
  });

  it('offers brands with the fab prefix', () => {
    const input = mq(IconInput, { bidi: Stream('') });

    input.click('.IconInput-toggle');
    input.click('.IconPicker-styles .Button:last-child');

    expect(input).toHaveElement('.IconPicker-icon[title="fab fa-github"]');
  });
});
