/**
 * The `username` helper displays a user's username in a <span class="username">
 * tag. If the user doesn't exist, the username will be displayed as [deleted].
 *
 * @param {User} user
 */
export default function username(user): any {
  const name = (user && user.displayName()) || app.translator.trans('core.lib.username.deleted_text');

  return <span className="username">{name}</span>;
}
