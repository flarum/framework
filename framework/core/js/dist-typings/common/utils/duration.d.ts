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
export declare const DURATION_UNITS: {
    unit: DurationUnit;
    seconds: number;
}[];
/**
 * Express a number of seconds in the largest unit that divides it exactly.
 *
 * Exactly is the important part: 90 minutes stays 90 minutes rather than
 * becoming 1.5 hours, because a fraction cannot be typed back into a field
 * expecting whole units and would be lost on the next save.
 */
export declare function toDuration(seconds: number): Duration;
/**
 * Convert a value and unit back into the seconds that get stored.
 */
export declare function toSeconds(value: number, unit: DurationUnit): number;
