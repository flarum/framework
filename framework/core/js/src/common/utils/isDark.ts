import { fireDeprecationWarning } from '../helpers/fireDebugWarning';

/** Red, green and blue 0–255, alpha 0–1. */
type RGBA = [number, number, number, number];

function alpha(value: string | undefined): number {
  if (value === undefined) return 1;

  return Math.min(1, Math.max(0, value.endsWith('%') ? parseFloat(value) / 100 : parseFloat(value)));
}

/**
 * Parses a hex (3, 4, 6 or 8 digits, with or without the `#`), `rgb()` or
 * `hsl()` colour.
 */
function parseColor(color: string): RGBA | null {
  const value = color.trim();

  const rgb = value.match(/^rgba?\(\s*([\d.]+)[\s,]+([\d.]+)[\s,]+([\d.]+)(?:\s*[,/]\s*([\d.]+%?))?\s*\)$/i);
  if (rgb) return [Number(rgb[1]), Number(rgb[2]), Number(rgb[3]), alpha(rgb[4])];

  const hsl = value.match(/^hsla?\(\s*([\d.]+)(?:deg)?[\s,]+([\d.]+)%[\s,]+([\d.]+)%(?:\s*[,/]\s*([\d.]+%?))?\s*\)$/i);
  if (hsl) {
    const h = Number(hsl[1]) % 360;
    const sat = Number(hsl[2]) / 100;
    const light = Number(hsl[3]) / 100;
    const k = (n: number) => (n + h / 30) % 12;
    const a = sat * Math.min(light, 1 - light);
    const f = (n: number) => Math.round(255 * (light - a * Math.max(-1, Math.min(k(n) - 3, 9 - k(n), 1))));

    return [f(0), f(8), f(4), alpha(hsl[4])];
  }

  let hex = value.replace(/^#/, '');
  if (hex.length === 3 || hex.length === 4) hex = hex.replace(/./g, '$&$&');
  if (!/^([0-9a-f]{6}|[0-9a-f]{8})$/i.test(hex)) return null;

  const [r, g, b, a = 255] = hex.match(/../g)!.map((pair) => parseInt(pair, 16));

  return [r, g, b, a / 255];
}

const resolved = new Map<string, RGBA | null>();

/**
 * Parses any CSS colour: hex, `rgb()` and `hsl()` directly, anything else
 * (named colours, `oklch()`, `color-mix()`, …) by painting it on a canvas,
 * which converts it to sRGB. Cached.
 */
function resolveColor(color: string): RGBA | null {
  const value = color.trim();
  if (!value) return null;

  const parsed = parseColor(value);
  if (parsed) return parsed;

  if (!resolved.has(value)) {
    resolved.set(value, paint(value));
  }

  return resolved.get(value)!;
}

function paint(color: string): RGBA | null {
  // These are valid for `color`, but not colours of their own: a canvas
  // ignores them and paints black.
  if (/var\(|^(inherit|initial|unset|revert|revert-layer|currentcolor)$/i.test(color)) return null;

  // A canvas ignores a colour it can't read, so check it is one first.
  const style = document.createElement('span').style;
  style.color = color;
  if (!style.color) return null;

  const context = document.createElement('canvas').getContext('2d', { willReadFrequently: true });
  if (!context) return null;

  context.fillStyle = color;
  context.fillRect(0, 0, 1, 1);
  const [r, g, b, a] = context.getImageData(0, 0, 1, 1).data;

  return [r, g, b, a / 255];
}

/**
 * A colour as it shows over an opaque background.
 */
function over([r, g, b, a]: RGBA, background: RGBA): RGBA {
  return [r * a + background[0] * (1 - a), g * a + background[1] * (1 - a), b * a + background[2] * (1 - a), 1];
}

/**
 * WCAG 2 relative luminance. See https://www.w3.org/TR/WCAG21/#dfn-relative-luminance.
 */
function luminance([r, g, b]: RGBA): number {
  const [lr, lg, lb] = [r, g, b].map((channel) => {
    const c = channel / 255;
    return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
  });

  return 0.2126 * lr + 0.7152 * lg + 0.0722 * lb;
}

function contrast(a: number, b: number): number {
  return (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
}

let warnedAboutThreshold = false;

/**
 * The `isDark` utility tells whether a colour needs light text on it: it
 * returns true when `--text-on-dark` (white by default) has a higher WCAG
 * contrast ratio against the colour than `--text-on-light` does. This is the
 * same choice CSS `contrast-color()` makes, against the theme's own text
 * colours rather than plain white and black. A translucent colour is taken
 * as it shows over the page background (`--body-bg`).
 *
 * A theme that sets `--yiq-threshold` gets the older YIQ brightness cut-off
 * instead (see https://www.w3.org/TR/AERT/#color-contrast). That override is
 * deprecated and will be removed in Flarum 3.0.
 */
export default function isDark(hexcolor: string | null): boolean {
  if (!hexcolor || hexcolor.length < 4) {
    return false;
  }

  const color = resolveColor(hexcolor);
  if (!color) return false;

  const style = getComputedStyle(document.body);
  const page = over(resolveColor(style.getPropertyValue('--body-bg')) ?? [255, 255, 255, 1], [255, 255, 255, 1]);
  const background = over(color, page);

  const threshold = parseInt(style.getPropertyValue('--yiq-threshold').trim());
  if (threshold) {
    if (!warnedAboutThreshold) {
      warnedAboutThreshold = true;
      fireDeprecationWarning(
        'The --yiq-threshold CSS property is deprecated: text on coloured backgrounds is chosen by contrast ratio.',
        '5447',
        '3.0',
        'flarum/framework'
      );
    }

    const [r, g, b] = background;
    return (r * 299 + g * 587 + b * 114) / 1000 < threshold;
  }

  const textOnDark = over(resolveColor(style.getPropertyValue('--text-on-dark')) ?? [255, 255, 255, 1], background);
  const textOnLight = over(resolveColor(style.getPropertyValue('--text-on-light')) ?? [0, 0, 0, 1], background);
  const backgroundLuminance = luminance(background);

  return contrast(backgroundLuminance, luminance(textOnDark)) >= contrast(backgroundLuminance, luminance(textOnLight));
}
