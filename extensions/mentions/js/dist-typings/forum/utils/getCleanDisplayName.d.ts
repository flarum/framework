/**
 * Fetches a user's username or display name.
 *
 * @param user An instance of the User model to fetch the username for
 * @param useDisplayName If `true`, uses `user.displayName()`, otherwise, uses `user.username()`
 */
export default function getCleanDisplayName(user: any, useDisplayName?: boolean): any;
export function shouldUseOldFormat(): {};
