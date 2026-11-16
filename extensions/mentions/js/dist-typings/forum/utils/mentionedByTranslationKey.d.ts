/**
 * Pick the translation key for the "x replied to this" summary under a post.
 *
 * Three cases need their own string so languages that inflect the verb or the
 * pronoun can translate each one correctly:
 *
 * - only you replied: "You replied to this."
 * - you and others replied: "You and Xyz replied to this."
 * - only others replied: "Xyz replied to this."
 *
 * @param {boolean} selfFirst Whether the first replier is the current user.
 * @param {number} count How many names are listed, including an "others" entry.
 * @return {string}
 */
export default function mentionedByTranslationKey(selfFirst: boolean, count: number): string;
