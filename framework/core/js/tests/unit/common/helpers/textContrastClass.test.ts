import textContrastClass from '../../../../src/common/helpers/textContrastClass';

describe('textContrastClass', () => {
  it('returns text-contrast--unchanged for null', () => {
    expect(textContrastClass(null)).toBe('text-contrast--unchanged');
  });

  it('returns text-contrast--unchanged for undefined', () => {
    expect(textContrastClass(undefined)).toBe('text-contrast--unchanged');
  });

  it('returns text-contrast--unchanged for an empty string', () => {
    expect(textContrastClass('')).toBe('text-contrast--unchanged');
  });

  it('returns text-contrast--light for a dark colour (needs light text)', () => {
    // #064635 dark green — the colour that triggered issue #4440
    expect(textContrastClass('#064635')).toBe('text-contrast--light');
  });

  it('returns text-contrast--light for #000000', () => {
    expect(textContrastClass('#000000')).toBe('text-contrast--light');
  });

  it('returns text-contrast--dark for a light colour (needs dark text)', () => {
    expect(textContrastClass('#ffffff')).toBe('text-contrast--dark');
  });

  it('returns text-contrast--dark for a light yellow', () => {
    expect(textContrastClass('#E8D8B0')).toBe('text-contrast--dark');
  });

  it('returns text-contrast--light for the default Flarum primary (#536F90)', () => {
    expect(textContrastClass('#536F90')).toBe('text-contrast--light');
  });
});
