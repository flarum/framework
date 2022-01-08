import app from 'flarum/forum/app';
import extractText from 'flarum/common/utils/extractText';

/**
 * Whether to use the old mentions format.
 *
 * `'@username'` or `'@"Display name"'`
 */
export const shouldUseOldFormat = () => app.forum.attribute('allowUsernameMentionFormat') || false;

const getDeletedUserText = () => extractText(app.translator.trans('core.lib.username.deleted_text'));

/**
 * Fetches a user's username or display name.
 *
 * Chooses based on the format option set in the admin settings page.
 *
 * @param user An instance of the User model to fetch the username for
 * @param useDisplayName If `true`, uses `user.displayName()`, otherwise, uses `user.username()`
 */
export default function getCleanDisplayName(user, useDisplayName = true) {
  if (!user) return getDeletedUserText().replace(/"#[a-z]{0,3}[0-9]+/, '_');

  const text = (useDisplayName ? user.displayName() : user.username()) || getDeletedUserText();

  return text.replace(/"#[a-z]{0,3}[0-9]+/, '_');
}
