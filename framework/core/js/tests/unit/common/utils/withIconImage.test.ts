import withIconImage from '../../../../src/common/utils/withIconImage';
import Group from '../../../../src/common/models/Group';

/**
 * withIconImage adds classes to a model's icon that show its uploaded image
 * in the glyph's place, so that every icon renderer shows it unchanged.
 */
describe('withIconImage', () => {
  beforeAll(() => {
    // jsdom has no CSS.supports(); the result is cached on first use.
    (globalThis as any).CSS = { supports: () => true };
  });

  const imageClass = (classes: string) => classes.split(' ').find((name) => /^IconImage--\d+$/.test(name))!;

  const declaration = (className: string) => {
    const style = document.querySelector('style[data-icon-images]') as HTMLStyleElement;
    const rule = Array.from(style.sheet!.cssRules).find((rule) => (rule as CSSStyleRule).selectorText === `.${className}`) as CSSStyleRule;

    return rule?.style.getPropertyValue('--icon-image').trim();
  };

  test('leaves the icon unchanged without an image', () => {
    expect(withIconImage('fas fa-bolt', null)).toBe('fas fa-bolt');
    expect(withIconImage(null, undefined)).toBeNull();
    expect(withIconImage(undefined, '')).toBeUndefined();
  });

  test('adds the image classes to the icon', () => {
    const classes = withIconImage('fas fa-bolt', 'https://example.com/assets/icons/a.webp');

    expect(classes).toMatch(/^fas fa-bolt IconImage IconImage--\d+$/);
  });

  test('marks an image without an icon', () => {
    expect(withIconImage(null, 'https://example.com/assets/icons/b.webp')).toMatch(/^IconImage IconImage--\d+ IconImage--only$/);
    expect(withIconImage('', 'https://example.com/assets/icons/b.webp')).toMatch(/^IconImage IconImage--\d+ IconImage--only$/);
  });

  test('gives each image one class', () => {
    const first = imageClass(withIconImage('fas fa-bolt', 'https://example.com/assets/icons/c.webp')!);

    expect(imageClass(withIconImage('fas fa-tag', 'https://example.com/assets/icons/c.webp')!)).toBe(first);
    expect(imageClass(withIconImage('fas fa-bolt', 'https://example.com/assets/icons/d.webp')!)).not.toBe(first);
    expect(imageClass(withIconImage('fas fa-bolt', 'https://example.com/assets/icons/c.webp', 'https://example.com/c.webp 1x')!)).not.toBe(first);
  });

  test('adds a rule that sets the image as --icon-image', () => {
    const className = imageClass(withIconImage(null, 'https://example.com/assets/icons/e.webp')!);

    expect(declaration(className)).toBe('url("https://example.com/assets/icons/e.webp")');
  });

  test('uses the HiDPI variants of a density srcset', () => {
    const className = imageClass(
      withIconImage(
        null,
        'https://example.com/assets/icons/f.webp',
        'https://example.com/assets/icons/f.webp 1x, https://example.com/assets/icons/f@2x.webp 2x'
      )!
    );

    expect(declaration(className)).toBe(
      'image-set(url("https://example.com/assets/icons/f.webp") 1x, url("https://example.com/assets/icons/f@2x.webp") 2x)'
    );
  });

  test('ignores a srcset of widths, which image-set() cannot take', () => {
    const className = imageClass(
      withIconImage(null, 'https://example.com/assets/icons/g.webp', 'https://example.com/assets/icons/g.webp 64w, https://example.com/g@2x.webp 128w')!
    );

    expect(declaration(className)).toBe('url("https://example.com/assets/icons/g.webp")');
  });

  test('escapes the URL', () => {
    const className = imageClass(withIconImage(null, 'https://example.com/assets/icons/h".webp')!);

    expect(declaration(className)).toBe('url("https://example.com/assets/icons/h\\".webp")');
  });

  test("a group's icon shows its image, and the stored icon stays available", () => {
    const group = new Group({
      type: 'groups',
      id: '4',
      attributes: { icon: 'fas fa-bolt', iconImageUrl: 'https://example.com/assets/icons/i.webp', iconImageSrcset: null },
    });

    expect(group.icon()).toMatch(/^fas fa-bolt IconImage IconImage--\d+$/);
    expect(group.attribute('icon')).toBe('fas fa-bolt');

    group.pushAttributes({ iconImageUrl: null });

    expect(group.icon()).toBe('fas fa-bolt');
  });
});
