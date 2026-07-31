import applyIconStyle from '../../../../src/common/utils/applyIconStyle';

/**
 * applyIconStyle rewrites the STYLE classes of a FontAwesome icon class list
 * to a forced variant, leaving the icon name, utility classes and brand
 * icons untouched. It must recognize both the short (fas) and long
 * (fa-solid) syntax for every family.
 */
describe('applyIconStyle', () => {
  const style = 'fa-duotone fa-light';

  test.each([
    // Short syntax, all families
    ['fas fa-user', 'fa-duotone fa-light fa-user'],
    ['far fa-user', 'fa-duotone fa-light fa-user'],
    ['fal fa-user', 'fa-duotone fa-light fa-user'],
    ['fat fa-user', 'fa-duotone fa-light fa-user'],
    ['fad fa-user', 'fa-duotone fa-light fa-user'],
    // Long syntax, all families
    ['fa-solid fa-user', 'fa-duotone fa-light fa-user'],
    ['fa-regular fa-user', 'fa-duotone fa-light fa-user'],
    ['fa-light fa-user', 'fa-duotone fa-light fa-user'],
    ['fa-thin fa-user', 'fa-duotone fa-light fa-user'],
    ['fa-duotone fa-user', 'fa-duotone fa-light fa-user'],
    // FA4-style bare base class
    ['fa fa-user', 'fa-duotone fa-light fa-user'],
    // Sharp: short and compound long syntax replaced atomically
    ['fass fa-user', 'fa-duotone fa-light fa-user'],
    ['fa-sharp fa-solid fa-user', 'fa-duotone fa-light fa-user'],
    ['fa-sharp-duotone fa-solid fa-user', 'fa-duotone fa-light fa-user'],
    // Style position does not matter
    ['fa-user fa-solid', 'fa-duotone fa-light fa-user'],
  ])('rewrites the style: %s', (input, expected) => {
    expect(applyIconStyle(input, style)).toBe(expected);
  });

  test.each([
    // Brand icons never get restyled — the glyphs only exist in the brands family.
    ['fab fa-github', 'fab fa-github'],
    ['fa-brands fa-github', 'fa-brands fa-github'],
  ])('leaves brands alone: %s', (input, expected) => {
    expect(applyIconStyle(input, style)).toBe(expected);
  });

  test('icon names that resemble style classes are not touched', () => {
    // fa-lightbulb contains "light"; fa-thin-line is a hypothetical name.
    expect(applyIconStyle('fas fa-lightbulb', style)).toBe('fa-duotone fa-light fa-lightbulb');
    expect(applyIconStyle('fa-solid fa-thumbs-up', style)).toBe('fa-duotone fa-light fa-thumbs-up');
  });

  test('utility classes pass through untouched', () => {
    expect(applyIconStyle('fas fa-user fa-fw fa-spin fa-2x', style)).toBe('fa-duotone fa-light fa-user fa-fw fa-spin fa-2x');
  });

  test('a name with no style class gains the forced style', () => {
    // Icons rendered with just a name rely on the css default; forcing makes
    // the choice explicit.
    expect(applyIconStyle('fa-user', style)).toBe('fa-duotone fa-light fa-user');
  });

  test('an empty style returns the name unchanged', () => {
    expect(applyIconStyle('fas fa-user', '')).toBe('fas fa-user');
  });

  test('non-string names pass through instead of throwing mid-render', () => {
    expect(applyIconStyle(undefined, style)).toBeUndefined();
    expect(applyIconStyle(null, style)).toBeNull();
  });

  test('duplicate style classes collapse into one forced style', () => {
    expect(applyIconStyle('fas fa-solid fa-user', style)).toBe('fa-duotone fa-light fa-user');
  });
});
