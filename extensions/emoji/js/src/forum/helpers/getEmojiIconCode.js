/*! Copyright Twitter Inc. and other contributors. Licensed under MIT */ /*
  https://github.com/twitter/twemoji/blob/gh-pages/LICENSE
*/

import twemoji from 'twemoji';

// avoid using a string literal like '\u200D' here because minifiers expand it inline
const U200D = String.fromCharCode(0x200d);

// avoid runtime RegExp creation for not so smart,
// not JIT based, and old browsers / engines
const UFE0Fg = /\uFE0F/g;

/**
 * Used to both remove the possible variant
 *  and to convert utf16 into code points.
 *  If there is a zero-width-joiner (U+200D), leave the variants in.
 * @param   string    the raw text of the emoji match
 * @return  string    the code point
 */
export default function getEmojiIconCode(emoji) {
  return twemoji.convert.toCodePoint(emoji.indexOf(U200D) < 0 ? emoji.replace(UFE0Fg, '') : emoji);
}
