import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';
import type User from 'flarum/common/models/User';
import revalidateMentionsPage from './revalidateMentionsPage';

/**
 * One user whose mention count an update changed, as the update response
 * carries it in the `affectedMentionUsers` attribute.
 */
interface AffectedMentionUser {
  id: string | number;
  /** Only present when the reader may see that user's count. */
  mentionCount?: number;
  /** Sent exactly when `mentionCount` is. */
  mentionCountRevision?: number;
}

/**
 * Bring the badge and an open Mentions tab up to date after a post is saved.
 *
 * Editing, hiding, restoring and approving a post can change who it mentions
 * or whether those mentions count. When that happens the update response names
 * the users whose count changed in the `affectedMentionUsers` attribute, with
 * their new counts, and an open tab that belongs to one of them reloads, so the
 * post leaves or rejoins the list the count describes.
 *
 * A save can also be about something else: liking a post saves `isLiked`,
 * which mentions know nothing about, and an edit that only fixes a typo leaves
 * every mention where it was, so the response names nobody. Reloading then
 * would replace a list the reader has paged through with its first page, so
 * only saves that actually reported a changed count are followed.
 *
 * The users named are not necessarily the user whose tab is open: one edit can
 * add a mention of B to a post that already mentions A, and then only B's list
 * changes. So the hook hands the named users to the reload, which decides
 * whether the tab on screen belongs to one of them.
 *
 * A new post is a create, and its response has no `affectedMentionUsers`
 * attribute because that is only sent on updates. The Create endpoint includes
 * `mentionsUsers` instead, and the store has already taken their counts from
 * the side-loaded users, so the same decision is made from that relationship:
 * no mentioned users means no count changed and the tab is left alone.
 *
 * Responses may arrive in any order, and so may the counts they carry. That is
 * not decided here: the User model keeps the count with the highest
 * `mentionCountRevision` (see keepNewestMentionCount), so applying an older
 * response's count after a newer one's changes nothing.
 *
 * The attribute is read from the response, not from the stored post: the
 * endpoint always sends it on an update, whatever `include` the caller named,
 * and an explicit empty array means no count changed. Only a sparse fieldset
 * such as `fields[posts]=content` can leave it out; the response then cannot
 * say, and the list reloads as the safe answer. That conservative reload is
 * accepted for now: Flarum 2 has no extender for resource meta, and adding one
 * is core work rather than part of this change.
 */
export default function refreshMentionsOnPostSave(): void {
  extend(Post.prototype, 'save', function (result, attributes) {
    if (!('content' in attributes || 'isHidden' in attributes || 'isApproved' in attributes)) return;

    // `exists` is still false here for a new post: the save only marks it as
    // existing when the response is pushed into the store.
    const created = !this.exists;

    result?.then(
      (post) => {
        const data = post.payload?.data;

        if (created) {
          // The create response reports the users the new post mentions, with
          // `[]` when it mentions nobody.
          const mentioned = data?.relationships?.mentionsUsers?.data;

          if (!Array.isArray(mentioned)) {
            revalidateMentionsPage(null);
            return;
          }

          if (mentioned.length > 0) revalidateMentionsPage(mentioned.map((user) => String(user.id)));

          return;
        }

        const affected = data?.attributes?.affectedMentionUsers;

        if (Array.isArray(affected)) {
          const users = affected as AffectedMentionUser[];

          applyCounts(users);

          if (users.length > 0) revalidateMentionsPage(users.map((user) => String(user.id)));

          return;
        }

        // The response cannot say which users changed (a sparse fieldset
        // left the attribute out), so reloading is the safe answer.
        revalidateMentionsPage(null);
      },
      () => {}
    );
  });
}

/**
 * Give the users already in the store the counts from an update response.
 *
 * The response can name users the reader has not loaded, and pushing it would
 * create user records holding only an id and a count, so only models already
 * in the store are updated. A count is only applied when the actor may read
 * it, which the server decides by leaving it out; the model drops it if it
 * already holds a newer one.
 */
function applyCounts(affected: AffectedMentionUser[]): void {
  let updated = false;

  for (const { id, mentionCount, mentionCountRevision } of affected) {
    const user = app.store.getById<User>('users', String(id));

    if (!user || typeof mentionCount !== 'number') continue;

    user.pushAttributes(typeof mentionCountRevision === 'number' ? { mentionCount, mentionCountRevision } : { mentionCount });
    updated = true;
  }

  if (updated) m.redraw();
}
