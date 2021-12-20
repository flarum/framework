import app from '../app';

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
export default function fireDebugWarning(...args: Parameters<typeof console.warn>): void {
  if (!app.forum.attribute('debug')) return;

  console.warn(...args);
}

/**
 * Fire a Flarum deprecation warning which is shown in the JS console.
 *
 * These warnings are only shown when the forum is in debug mode, and the function exists to
 * reduce bundle size caused by multiple warnings across our JavaScript.
 *
 * @param message The message to display. (Short, but sweet, please!)
 * @param githubId The PR or Issue ID with more info in relation to this change.
 * @param [removedFrom] The version in which this feature will be completely removed. (default: 2.0)
 * @param [repo] The repo which the issue or PR is located in. (default: flarum/core)
 *
 * @see {@link fireDebugWarning}
 */
export function fireDeprecationWarning(message: string, githubId: string, removedFrom: string = '2.0', repo: string = 'flarum/core'): void {
  // GitHub auto-redirects between `/pull` and `/issues` for us, so using `/pull` saves 2 bytes!
  fireDebugWarning(`[Flarum ${removedFrom} Deprecation] ${message}\n\nSee: https://github.com/${repo}/pull/${githubId}`);
}
