/**
 * Calls `console.warn` with the provided arguments, but only if the forum is in debug mode.
 *
 * This function is intended to provide warnings to extension developers about issues with
 * their extensions that may not be easily noticed when testing, such as accessibility
 * issues.
 *
 * These warnings should be hidden on production forums to ensure webmasters are not
 * inundated with do-gooders telling them they have an issue when it isn't something they
 * can fix.
 */
export default function fireDebugWarning(...args: Parameters<typeof console.warn>): void;
