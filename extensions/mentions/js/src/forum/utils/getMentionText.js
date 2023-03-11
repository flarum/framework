import app from 'flarum/forum/app';

/**
 * Fetches the mention text for a specified user (and optionally a post ID for replies or group).
 *
 * Automatically determines which mention syntax to be used based on the option in the
 * admin dashboard. Also performs display name clean-up automatically.
 *
 * @deprecated Use `app.mentionables.get('user').replacement(user)` instead. Will be removed in 2.0.
 */
export default function getMentionText(user, postId, group) {
  if (user !== undefined && postId === undefined) {
    return app.mentionables.get('user').replacement(user);
  } else if (user !== undefined && postId !== undefined) {
    return app.mentionables.get('post').replacement(app.store.getById('posts', postId));
  } else if (group !== undefined) {
    return app.mentionables.get('group').replacement(group);
  }

  throw 'No parameters were passed';
}
