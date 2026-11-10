import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import isDark from '../../../../src/common/utils/isDark';

// isDark reads --text-on-dark, --text-on-light and --yiq-threshold from
// getComputedStyle(document.body). In jsdom they are unset unless a test sets
// them, so the text colours fall back to white and black.

const properties = ['--text-on-dark', '--text-on-light', '--yiq-threshold', '--body-bg'];

function setProperty(name: string, value: string) {
  document.body.style.setProperty(name, value);
}

describe('isDark', () => {
  beforeAll(() => {
    // Debug mode, so deprecation warnings are shown.
    bootstrapForum({ resources: [{ type: 'forums', id: '1', attributes: { debug: true } }] });
  });

  afterEach(() => {
    properties.forEach((name) => document.body.style.removeProperty(name));
  });

  // -------------------------------------------------------------------------
  // Input guards
  // -------------------------------------------------------------------------

  it('returns false for null', () => {
    expect(isDark(null)).toBe(false);
  });

  it('returns false for an empty string', () => {
    expect(isDark('')).toBe(false);
  });

  it('returns false for a string shorter than 4 characters', () => {
    expect(isDark('#33')).toBe(false);
  });

  it('returns false for a value that is not a colour', () => {
    expect(isDark('not-a-colour')).toBe(false);
  });

  // -------------------------------------------------------------------------
  // Formats
  // -------------------------------------------------------------------------

  it('treats #000 as dark', () => {
    expect(isDark('#000')).toBe(true);
  });

  it('treats #fff as light', () => {
    expect(isDark('#fff')).toBe(false);
  });

  it('treats #000000 as dark', () => {
    expect(isDark('#000000')).toBe(true);
  });

  it('treats #ffffff as light', () => {
    expect(isDark('#ffffff')).toBe(false);
  });

  it('accepts hex without a leading #', () => {
    expect(isDark('000000')).toBe(true);
  });

  it('accepts rgb() colours', () => {
    expect(isDark('rgb(0, 0, 0)')).toBe(true);
    expect(isDark('rgb(231, 103, 46)')).toBe(false);
  });

  it('accepts surrounding whitespace, as computed custom properties can have', () => {
    expect(isDark(' #000000')).toBe(true);
  });

  // -------------------------------------------------------------------------
  // Contrast ratio: true when light text contrasts better than dark text
  // -------------------------------------------------------------------------

  it.each([
    ['#48BF83', 'mint'],
    ['#6DBB3E', 'green'],
    ['#1FA39A', 'teal'],
    ['#4B93D1', 'blue'],
    ['#E7672E', 'Flarum orange'],
    ['#FF3333', 'red'],
    ['#F1C40F', 'yellow'],
    ['#E8D8B0', 'light yellow'],
  ])('treats %s (%s) as light: dark text contrasts better', (colour) => {
    expect(isDark(colour)).toBe(false);
  });

  it.each([
    ['#536F90', 'default Flarum primary'],
    ['#B72A2A', 'crimson'],
    ['#064635', 'dark green'],
    ['#800000', 'maroon'],
    ['#9B59B6', 'purple'],
    ['#5C6BC0', 'indigo'],
    // Mid-tones the old fixed high-contrast threshold (YIQ 80) gave dark text.
    ['#D33670', 'rose'],
    ['#8648E1', 'violet'],
    ['#566F92', 'grey-blue'],
    ['#516780', 'slate'],
    ['#C41D33', 'crimson'],
  ])('treats %s (%s) as dark: light text contrasts better', (colour) => {
    expect(isDark(colour)).toBe(true);
  });

  it('compares against the resolved --text-on-light colour', () => {
    // #777777 against white: 4.48. Against black: 4.69, so dark text wins.
    expect(isDark('#777777')).toBe(false);

    // Against Flarum's default --text-on-light (#14191f): 3.95, so white wins.
    setProperty('--text-on-light', '#14191f');
    expect(isDark('#777777')).toBe(true);
  });

  it('compares against the resolved --text-on-dark colour', () => {
    // #6E6E6E against white: 5.10, against black: 4.12, so white wins.
    expect(isDark('#6E6E6E')).toBe(true);

    // A greyer light text colour (#cccccc) drops that to 3.18.
    setProperty('--text-on-dark', '#cccccc');
    expect(isDark('#6E6E6E')).toBe(false);
  });

  it('reads rgb() text colours', () => {
    setProperty('--text-on-light', 'rgb(20, 25, 31)');
    expect(isDark('#777777')).toBe(true);
  });

  it('reads hsl() text colours, as Less compiles --text-on-light', () => {
    setProperty('--text-on-light', 'hsl(214.15384615,20%,10%)');
    expect(isDark('#777777')).toBe(true);
  });

  it('composites translucent colours over the page background', () => {
    // 10% black over the default white page is nearly white.
    expect(isDark('rgba(0, 0, 0, 0.1)')).toBe(false);
    expect(isDark('rgb(0 0 0 / 10%)')).toBe(false);
    expect(isDark('#0000001a')).toBe(false);
    expect(isDark('#0001')).toBe(false);
    expect(isDark('hsla(0, 0%, 0%, 0.1)')).toBe(false);

    // Opaque alpha changes nothing.
    expect(isDark('rgba(0, 0, 0, 1)')).toBe(true);
    expect(isDark('#000000ff')).toBe(true);

    // 10% white over a dark page is nearly black.
    setProperty('--body-bg', '#111111');
    expect(isDark('rgba(255, 255, 255, 0.1)')).toBe(true);
  });

  it('accepts hsl() colours', () => {
    expect(isDark('hsl(0, 0%, 0%)')).toBe(true);
    expect(isDark('hsl(150, 48%, 52%)')).toBe(false);
  });

  // -------------------------------------------------------------------------
  // Deprecated --yiq-threshold override
  // -------------------------------------------------------------------------

  it('uses the YIQ brightness threshold when a theme sets --yiq-threshold, with one deprecation warning', () => {
    const warn = jest.spyOn(console, 'warn').mockImplementation(() => {});

    // #E7672E has a YIQ brightness of about 135.
    setProperty('--yiq-threshold', '150');
    expect(isDark('#E7672E')).toBe(true);

    setProperty('--yiq-threshold', '128');
    expect(isDark('#E7672E')).toBe(false);

    expect(warn).toHaveBeenCalledTimes(1);
    expect(warn.mock.calls[0][0]).toContain('--yiq-threshold');
    warn.mockRestore();
  });

  it('does not warn without --yiq-threshold', () => {
    const warn = jest.spyOn(console, 'warn').mockImplementation(() => {});
    isDark('#E7672E');
    expect(warn).not.toHaveBeenCalled();
    warn.mockRestore();
  });

  it('paints colours it cannot parse, such as named colours, on a canvas', () => {
    const fillRect = jest.fn();
    const getContext = jest.spyOn(HTMLCanvasElement.prototype, 'getContext').mockImplementation(
      () => ({ fillStyle: '', fillRect, getImageData: () => ({ data: [102, 51, 153, 255] }) } as unknown as CanvasRenderingContext2D)
    );

    // rebeccapurple, #663399: white contrasts better.
    expect(isDark('rebeccapurple')).toBe(true);
    expect(fillRect).toHaveBeenCalledTimes(1);

    // Cached.
    expect(isDark('rebeccapurple')).toBe(true);
    expect(fillRect).toHaveBeenCalledTimes(1);

    getContext.mockRestore();
  });

  it('does not paint values that are not colours of their own', () => {
    const fillRect = jest.fn();
    const getContext = jest.spyOn(HTMLCanvasElement.prototype, 'getContext').mockImplementation(
      () => ({ fillStyle: '', fillRect, getImageData: () => ({ data: [0, 0, 0, 255] }) } as unknown as CanvasRenderingContext2D)
    );

    for (const value of ['var(--primary-color)', 'inherit', 'initial', 'unset', 'revert', 'currentColor']) {
      expect(isDark(value)).toBe(false);
    }
    expect(fillRect).not.toHaveBeenCalled();

    getContext.mockRestore();
  });

  it('treats a colour it cannot paint as light rather than throwing', () => {
    const getContext = jest.spyOn(HTMLCanvasElement.prototype, 'getContext').mockImplementation(() => null);
    expect(isDark('darkslateblue')).toBe(false);
    getContext.mockRestore();
  });
});
