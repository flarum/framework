import MentionTextGenerator from './MentionTextGenerator';

/**
 * Fetches the mention text for a specified user (and optionally a post ID for replies, group, or tag).
 *
 * Automatically determines which mention syntax to be used based on the option in the
 * admin dashboard. Also performs display name clean-up automatically.
 *
 * @deprecated Use `MentionTextGenerator` instead. Remove in 2.0.
 *
 * @example <caption>New display name syntax</caption>
 * // '@"User"#1'
 * getMentionText(User) // User is ID 1, display name is 'User'
 *
 * @example <caption>Replying</caption>
 * // '@"User"#p13'
 * getMentionText(User, 13) // User display name is 'User', post ID is 13
 *
 * @example <caption>Using old syntax</caption>
 * // '@username'
 * getMentionText(User) // User's username is 'username'
 *
 * @example <caption>Group mention</caption>
 * // '@"Mods"#g4'
 * getMentionText(undefined, undefined, group) // Group display name is 'Mods', group ID is 4
 */
export default function getMentionText(user, postId, group) {
  const generator = new MentionTextGenerator();
  if (user !== undefined && postId === undefined) {
    return generator.forUser(user);
  } else if (user !== undefined && postId !== undefined) {
    return generator.forPostMention(user, postId);
  } else if (group !== undefined) {
    return generator.forGroup(group);
  } else {
    throw 'No parameters were passed';
  }
}
