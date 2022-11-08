import getCleanDisplayName, { shouldUseOldFormat } from './getCleanDisplayName';

/**
 * Fetches the mention text for a specified user (and optionally a post ID for replies, or group).
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
 *
 * @example <caption>Group mention</caption>
 * // '@"Mods"#g4'
 * getMentionText(undefined, undefined, group) // Group display name is 'Mods', group ID is 4
 */
export default function getMentionText(user, postId, group) {
  if (user !== undefined && postId === undefined) {
    if (shouldUseOldFormat()) {
      // Plain @username
      const cleanText = getCleanDisplayName(user, false);
      return `@${cleanText}`;
    }
    // @"Display name"#UserID
    const cleanText = getCleanDisplayName(user);
    return `@"${cleanText}"#${user.id()}`;
  } else if (user !== undefined && postId !== undefined) {
    // @"Display name"#pPostID
    const cleanText = getCleanDisplayName(user);
    return `@"${cleanText}"#p${postId}`;
  } else if (group !== undefined) {
    // @"Name Plural"#gGroupID
    return `@"${group.namePlural()}"#g${group.id()}`;
  } else {
    throw 'No parameters were passed';
  }
}
