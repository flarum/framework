import app from 'flarum/forum/app';
import MentionTextGenerator from './MentionTextGenerator';

/**
 * Fetches the mention text for a specified user (and optionally a post ID for replies or group).
 *
 * Automatically determines which mention syntax to be used based on the option in the
 * admin dashboard. Also performs display name clean-up automatically.
 *
 * @deprecated Use `MentionTextGenerator` instead. Will be removed in 2.0.
 */
export default function getMentionText(user, postId, group) {
  const mentionText = new MentionTextGenerator();

  if (user !== undefined && postId === undefined) {
    return mentionText.forUser(user);
  } else if (user !== undefined && postId !== undefined) {
    return mentionText.forPost(app.store.getById('posts', postId));
  } else if (group !== undefined) {
    return mentionText.forGroup(group);
  }

  throw 'No parameters were passed';
}
