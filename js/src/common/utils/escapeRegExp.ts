const specialChars = /[\\^$.*+?()[\]{}|]/g;

/**
 * Escapes the `RegExp` special characters "^", "$", "\", ".", "*", "+", "?", "(", ")", "[", "]", "{", "}", and "|" in `input`.
 *
 * The difference between this function and Lodash is that we blindly trust that the `input` is a string, not a number, Symbol,
 * `null` or anything else.
 *
 * Based on Lodash's `escapeRegExp`: https://github.com/lodash/lodash/blob/4.1.2-npm-packages/lodash.escaperegexp/index.js
 */
export default function escapeRegExp(input: string) {
  return specialChars.test(input) ? input.replace(specialChars, '\\$&') : input;
}
