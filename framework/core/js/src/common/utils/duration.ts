export type DurationUnit = 'seconds' | 'minutes' | 'hours' | 'days' | 'weeks' | 'years';

export interface Duration {
  value: number;
  unit: DurationUnit;
}

/**
 * The units a duration can be expressed in, largest first.
 *
 * A year here is 365 days and a week is 7 of those: these are for reading a
 * stored number of seconds back as something a person would recognise, not for
 * calendar arithmetic.
 */
export const DURATION_UNITS: { unit: DurationUnit; seconds: number }[] = [
  { unit: 'years', seconds: 31536000 },
  { unit: 'weeks', seconds: 604800 },
  { unit: 'days', seconds: 86400 },
  { unit: 'hours', seconds: 3600 },
  { unit: 'minutes', seconds: 60 },
  { unit: 'seconds', seconds: 1 },
];

/**
 * Express a number of seconds in the largest unit that divides it exactly.
 *
 * Exactly is the important part: 90 minutes stays 90 minutes rather than
 * becoming 1.5 hours, because a fraction cannot be typed back into a field
 * expecting whole units and would be lost on the next save.
 */
export function toDuration(seconds: number): Duration {
  if (!Number.isFinite(seconds) || seconds <= 0) {
    return { value: 0, unit: 'seconds' };
  }

  const whole = Math.floor(seconds);

  for (const { unit, seconds: size } of DURATION_UNITS) {
    if (whole % size === 0) {
      return { value: whole / size, unit };
    }
  }

  return { value: whole, unit: 'seconds' };
}

/**
 * Convert a value and unit back into the seconds that get stored.
 */
export function toSeconds(value: number, unit: DurationUnit): number {
  if (!Number.isFinite(value) || value <= 0) return 0;

  const size = DURATION_UNITS.find((u) => u.unit === unit)?.seconds ?? 1;

  return Math.floor(value * size);
}
