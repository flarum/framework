import { toDuration, toSeconds, DURATION_UNITS } from '../../../../src/common/utils/duration';

describe('toDuration', () => {
  it('picks the largest unit that divides exactly', () => {
    expect(toDuration(3600)).toEqual({ value: 1, unit: 'hours' });
    expect(toDuration(86400)).toEqual({ value: 1, unit: 'days' });
    expect(toDuration(604800)).toEqual({ value: 1, unit: 'weeks' });
  });

  it('keeps a whole number rather than reaching for a bigger unit', () => {
    // 90 minutes is not a whole number of hours, so minutes it stays — the
    // alternative is showing 1.5 hours, which cannot be typed back in.
    expect(toDuration(5400)).toEqual({ value: 90, unit: 'minutes' });
  });

  it('falls back to seconds when nothing else divides', () => {
    expect(toDuration(90)).toEqual({ value: 90, unit: 'seconds' });
    expect(toDuration(1)).toEqual({ value: 1, unit: 'seconds' });
  });

  it('describes the real-world defaults sensibly', () => {
    expect(toDuration(60 * 60)).toEqual({ value: 1, unit: 'hours' });
    expect(toDuration(5 * 365 * 24 * 60 * 60)).toEqual({ value: 5, unit: 'years' });
    expect(toDuration(14 * 24 * 60 * 60)).toEqual({ value: 2, unit: 'weeks' });
  });

  it('treats zero as seconds rather than the largest unit', () => {
    // Zero divides by everything, so without a special case it would come back
    // as "0 years", which reads as a very long time rather than "never".
    expect(toDuration(0)).toEqual({ value: 0, unit: 'seconds' });
  });

  it('never returns a negative duration', () => {
    expect(toDuration(-60)).toEqual({ value: 0, unit: 'seconds' });
  });
});

describe('toSeconds', () => {
  it('converts each unit', () => {
    expect(toSeconds(1, 'seconds')).toBe(1);
    expect(toSeconds(1, 'minutes')).toBe(60);
    expect(toSeconds(1, 'hours')).toBe(3600);
    expect(toSeconds(1, 'days')).toBe(86400);
    expect(toSeconds(1, 'weeks')).toBe(604800);
    expect(toSeconds(1, 'years')).toBe(31536000);
  });

  it('multiplies the value', () => {
    expect(toSeconds(14, 'days')).toBe(1209600);
    expect(toSeconds(5, 'years')).toBe(157680000);
  });

  it('floors a fractional value rather than storing one', () => {
    expect(toSeconds(1.5, 'hours')).toBe(5400);
  });

  it('clamps a negative value to zero', () => {
    expect(toSeconds(-5, 'days')).toBe(0);
  });

  it('treats an unknown unit as seconds', () => {
    expect(toSeconds(30, 'fortnights' as any)).toBe(30);
  });
});

describe('round trip', () => {
  it('survives conversion in both directions', () => {
    const values = [0, 1, 60, 3600, 86400, 604800, 1209600, 31536000, 157680000, 90, 5400];

    values.forEach((seconds) => {
      const { value, unit } = toDuration(seconds);
      expect(toSeconds(value, unit)).toBe(seconds);
    });
  });
});

describe('DURATION_UNITS', () => {
  it('is ordered from largest to smallest', () => {
    const sizes = DURATION_UNITS.map((u) => u.seconds);

    expect(sizes).toEqual([...sizes].sort((a, b) => b - a));
  });
});
