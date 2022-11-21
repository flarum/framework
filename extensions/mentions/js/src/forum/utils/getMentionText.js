import MentionTextGenerator from './MentionTextGenerator';

/**
 * Fetches the mention text for a specified user (and optionally a post ID for replies or group).
 *
 * Automatically determines which mention syntax to be used based on the option in the
 * admin dashboard. Also performs display name clean-up automatically.
 *
 * @deprecated Use `MentionTextGenerator` instead. Remove in 2.0.
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
