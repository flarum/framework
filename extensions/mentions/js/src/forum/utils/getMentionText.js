import getCleanDisplayName, { shouldUseOldFormat } from './getCleanDisplayName';

/**
 * Fetches the mention text for a specified user (and optionally a post ID for replies).
 *
 * Automatically determines which mention syntax to be used based on the option in the
 * admin dashboard. Also performs display name clean-up automatically.
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
 */
export default function getMentionText(user, postId) {
  if (postId === undefined) {
    if (shouldUseOldFormat()) {
      // Plain @username
      const cleanText = getCleanDisplayName(user, false);
      return `@${cleanText}`;
    }
    // @"Display name"#UserID
    const cleanText = getCleanDisplayName(user);
    return `@"${cleanText}"#${user.id()}`;
  } else {
    // @"Display name"#pPostID
    const cleanText = getCleanDisplayName(user);
    return `@"${cleanText}"#p${postId}`;
  }
}
