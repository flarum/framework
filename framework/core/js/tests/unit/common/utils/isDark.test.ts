import isDark from '../../../../src/common/utils/isDark';

// isDark uses getComputedStyle(document.body) to read --yiq-threshold.
// In jsdom that returns '' so the || 128 fallback applies throughout
// these tests, unless we override it explicitly.

function setYiqThreshold(value: number) {
  document.body.style.setProperty('--yiq-threshold', String(value));
}

function clearYiqThreshold() {
  document.body.style.removeProperty('--yiq-threshold');
}

describe('isDark', () => {
  afterEach(() => {
    clearYiqThreshold();
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

  // -------------------------------------------------------------------------
  // 3-character hex shorthand
  // -------------------------------------------------------------------------

  it('treats #000 as dark', () => {
    expect(isDark('#000')).toBe(true);
  });

  it('treats #fff as light', () => {
    expect(isDark('#fff')).toBe(false);
  });

  it('treats #333 as dark', () => {
    // YIQ = 51 < 128
    expect(isDark('#333')).toBe(true);
  });

  // -------------------------------------------------------------------------
  // 6-character hex
  // -------------------------------------------------------------------------

  it('treats #000000 as dark', () => {
    expect(isDark('#000000')).toBe(true);
  });

  it('treats #ffffff as light', () => {
    expect(isDark('#ffffff')).toBe(false);
  });

  it('treats #064635 (dark green, the issue reporter\'s colour) as dark', () => {
    // R=6 G=70 B=53 → YIQ ≈ 48.9 < 128
    expect(isDark('#064635')).toBe(true);
  });

  it('treats #536F90 (default Flarum primary, medium blue) as dark', () => {
    // R=83 G=111 B=144 → YIQ ≈ 106.4 < 128
    expect(isDark('#536F90')).toBe(true);
  });

  it('treats #E8D8B0 (light yellow) as light', () => {
    // R=232 G=216 B=176 → YIQ ≈ 216.2 > 128
    expect(isDark('#E8D8B0')).toBe(false);
  });

  it('treats #FFD700 (gold) as light', () => {
    // R=255 G=215 B=0 → YIQ ≈ 202.5 > 128
    expect(isDark('#FFD700')).toBe(false);
  });

  it('treats #800000 (dark red) as dark', () => {
    // R=128 G=0 B=0 → YIQ ≈ 38.3 < 128
    expect(isDark('#800000')).toBe(true);
  });

  // -------------------------------------------------------------------------
  // Custom --yiq-threshold (CSS variable)
  // -------------------------------------------------------------------------

  it('uses the --yiq-threshold CSS variable when set', () => {
    // #536F90 has YIQ ≈ 106.4; with threshold 80 it should be considered light
    setYiqThreshold(80);
    expect(isDark('#536F90')).toBe(false);
  });

  it('falls back to threshold 128 when --yiq-threshold is not set', () => {
    // #536F90 YIQ ≈ 106.4 < 128 → dark
    expect(isDark('#536F90')).toBe(true);
  });
});
