import getCleanDisplayName, { shouldUseOldFormat } from './getCleanDisplayName';
import type User from 'flarum/common/models/User';
import type Group from 'flarum/common/models/Group';
import type Post from 'flarum/common/models/Post';
import type Tag from 'flarum/tags/common/models/Tag';

/**
 * Fetches the mention text for a specified model.
 */
export default class MentionTextGenerator {
  /**
   * Automatically determines which mention syntax to be used based on the option in the
   * admin dashboard. Also performs display name clean-up automatically.
   *
   * @"Display name"#UserID or `@username`
   *
   * @example <caption>New display name syntax</caption>
   * // '@"user"#1'
   * forUser(User) // User is ID 1, display name is 'User'
   *
   * @example <caption>Using old syntax</caption>
   * // '@username'
   * forUser(user) // User's username is 'username'
   */
  public static forUser(user: User): string {
    if (shouldUseOldFormat()) {
      const cleanText = getCleanDisplayName(user, false);
      return `@${cleanText}`;
    }

    const cleanText = getCleanDisplayName(user);
    return `@"${cleanText}"#${user.id()}`;
  }

  /**
   * Generates the syntax for mentioning of a post. Also cleans up the display name.
   *
   * @example <caption>Post mention</caption>
   * // '@"User"#p13'
   * // @"Display name"#pPostID
   * forPostMention(user, 13) // User display name is 'User', post ID is 13
   */
  public static forPost(post: Post): string {
    const user = post.user();
    const cleanText = getCleanDisplayName(user);
    return `@"${cleanText}"#p${post.id()}`;
  }

  /**
   * Generates the mention syntax for a group mention.
   *
   * @"Name Plural"#gGroupID
   *
   * @example <caption>Group mention</caption>
   * // '@"Mods"#g4'
   * forGroup(group) // Group display name is 'Mods', group ID is 4
   */
  public static forGroup(group: Group): string {
    return `@"${group.namePlural()}"#g${group.id()}`;
  }
}
