/*! Copyright Twitter Inc. and other contributors. Licensed under MIT */ 
/**
 * Used to both remove the possible variant
 *  and to convert utf16 into code points.
 *  If there is a zero-width-joiner (U+200D), leave the variants in.
 * @param   string    the raw text of the emoji match
 * @return  string    the code point
 */
export default function getEmojiIconCode(emoji: string): string;
