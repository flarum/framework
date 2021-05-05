const specialChars = /[.*+?^${}()|[\]\\]/g;

/**
 * Escapes the `RegExp` special characters in `input`.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Regular_Expressions#escaping
 */
export default function escapeRegExp(input: string): string {
  return input.replace(specialChars, '\\$&');
}
