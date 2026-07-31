/**
 * FontAwesome style classes, in both the short (`fas`) and long (`fa-solid`)
 * syntax, plus the sharp modifier classes that pair with a style. This is a
 * closed list on purpose: icon names (`fa-lightbulb`) and utility classes
 * (`fa-fw`, `fa-spin`, `fa-2x`) also start with `fa-`, so anything
 * pattern-based would mangle them.
 */
const STYLE_CLASSES = new Set([
  'fa',
  'fas',
  'far',
  'fal',
  'fat',
  'fad',
  'fass',
  'fasr',
  'fasl',
  'fast',
  'fasds',
  'fa-solid',
  'fa-regular',
  'fa-light',
  'fa-thin',
  'fa-duotone',
  'fa-sharp',
  'fa-sharp-duotone',
]);

const BRAND_CLASSES = new Set(['fab', 'fa-brands']);

/**
 * Rewrite the style classes of a FontAwesome icon class list to the given
 * style, e.g. `fas fa-user` -> `fa-duotone fa-light fa-user`.
 *
 * Brand icons are never rewritten: their glyphs only exist in the brands
 * family, so forcing a style would blank them. Everything that isn't a style
 * class — the icon name and any utility classes — passes through untouched.
 */
export default function applyIconStyle(name: string | null | undefined, style: string): string | null | undefined {
  // Some callers legitimately render an Icon without a name; never throw
  // mid-render over it.
  if (!style || typeof name !== 'string') return name;

  const classes = name.split(/\s+/).filter(Boolean);

  if (classes.some((cls) => BRAND_CLASSES.has(cls))) {
    return name;
  }

  const kept = classes.filter((cls) => !STYLE_CLASSES.has(cls));

  return `${style} ${kept.join(' ')}`.trim();
}
